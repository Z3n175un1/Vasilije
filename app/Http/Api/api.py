# Api que consulta la pagina oficial del bcb del cual se sacará el tipo de cambio al dia actual:
# pagina:https://www.bcb.gob.bo/
# nombre de la clase para sacar: class="bcb-tco-amount"
# como tiene que salir: 11,68
# y la fecha de cuando se saco en texto: Jueves 15 de octubre 2026

import re
from datetime import date

import httpx
from bs4 import BeautifulSoup
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

URL_BCB = "https://www.bcb.gob.bo/"
CLASE_TCO = "bcb-tco-amount"
TIMEOUT = 15.0

app = FastAPI(title="API Tipo de Cambio BCB", version="1.0.0")

DIAS = ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado", "Domingo"]
MESES = [
    "enero", "febrero", "marzo", "abril", "mayo", "junio",
    "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre",
]


class TipoCambio(BaseModel):
    tipo_cambio: str
    fecha: str
    fecha_iso: str


def _fecha_en_texto(fecha_iso: str) -> str:
    anio, mes, dia = (int(p) for p in fecha_iso.split("-"))
    d = date(anio, mes, dia)
    return f"{DIAS[d.weekday()]} {dia} de {MESES[mes - 1]} {anio}"


def _limpiar(valor: str) -> str:
    valor = re.sub(r"\s+", " ", valor).strip()
    return valor.replace("\xa0", " ")


def _parsear_html(html: str) -> TipoCambio:
    soup = BeautifulSoup(html, "html.parser")

    nodo = soup.select_one(f"div.{CLASE_TCO}")
    if nodo is None:
        nodo = soup.select_one(f".{CLASE_TCO}")
    if nodo is None:
        raise ValueError(f"No se encontro el elemento con class='{CLASE_TCO}'")

    numero = nodo.select_one(".bcb-tco-num") or nodo
    tipo_cambio = _limpiar(numero.get_text())
    if not tipo_cambio:
        raise ValueError("El tipo de cambio vino vacio")

    fecha_iso = ""
    tarjeta = nodo.find_parent("article") or soup
    etiqueta = tarjeta.select_one(".bcb-kpi2-asof time[datetime]")
    if etiqueta is not None:
        fecha_iso = (etiqueta.get("datetime") or "").strip()

    if not fecha_iso:
        match = re.search(
            r"(\d{1,2})\s+de\s+([A-Za-zñ]+),?\s+(\d{4})",
            _limpiar(soup.get_text(" ")),
            re.IGNORECASE,
        )
        if match:
            dia, mes_txt, anio = match.groups()
            mes = MESES.index(mes_txt.lower()) + 1
            fecha_iso = f"{int(anio):04d}-{mes:02d}-{int(dia):02d}"

    if not fecha_iso:
        fecha_iso = date.today().isoformat()

    return TipoCambio(
        tipo_cambio=tipo_cambio,
        fecha=_fecha_en_texto(fecha_iso),
        fecha_iso=fecha_iso,
    )



def obtener_tipo_cambio() -> TipoCambio:
    import os
    os.environ.pop("SSL_CERT_FILE", None)
    os.environ.pop("REQUESTS_CA_BUNDLE", None)

    try:
        respuesta = httpx.get(
            URL_BCB,
            timeout=TIMEOUT,
            follow_redirects=True,
            headers={"User-Agent": "Mozilla/5.0 (API tipo de cambio BCB)"},
            verify=False,
        )
        respuesta.raise_for_status()
    except httpx.HTTPError as exc:
        raise HTTPException(status_code=502, detail=f"No se pudo consultar el BCB: {exc}") from exc

    try:
        return _parsear_html(respuesta.text)
    except ValueError as exc:
        raise HTTPException(status_code=502, detail=str(exc)) from exc


@app.get("/tipo-cambio", response_model=TipoCambio)
def tipo_cambio():
    return obtener_tipo_cambio()


@app.get("/")
def raiz():
    return {
        "servicio": "API Tipo de Cambio BCB",
        "fuente": URL_BCB,
        "endpoints": ["/tipo-cambio", "/docs"],
    }


if __name__ == "__main__":
    import sys

    if len(sys.argv) > 1 and sys.argv[1] == "server":
        import uvicorn

        uvicorn.run("api:app", host="0.0.0.0", port=8080, reload=True)
    else:
        import os
        os.environ.pop("SSL_CERT_FILE", None)
        os.environ.pop("REQUESTS_CA_BUNDLE", None)

        import httpx as _httpx
        import json as _json

        try:
            r = httpx.get(
                URL_BCB,
                timeout=TIMEOUT,
                follow_redirects=True,
                headers={"User-Agent": "Mozilla/5.0 (API tipo de cambio BCB)"},
                verify=False,
            )
            r.raise_for_status()
            datos = _parsear_html(r.text)
            print(f"Tipo de cambio: {datos.tipo_cambio}")
            print(f"Fecha:          {datos.fecha}")
            print(_json.dumps(datos.model_dump(), ensure_ascii=False))
        except (_httpx.HTTPError, ValueError) as exc:
            print(f"Error obteniendo el tipo de cambio: {exc}")
