<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
        <div class="bg-white w-48 h-64 rounded-lg">
    <div class="flex p-2 gap-1">
        <div class="">
            <span class="bg-blue-500 inline-block center w-3 h-3 rounded-full"></span>
        </div>
        <div class="circle">
            <span class="bg-purple-500 inline-block center w-3 h-3 rounded-full"></span>
        </div>
        <div class="circle">
            <span class="bg-pink-500 box inline-block center w-3 h-3 rounded-full"></span>
        </div>
    </div>
    <div class="card__content">
        <div class="titulo">
            <h1>
                Oppsi! esta pagina está en fase de pruebas
            </h1>
        </div>
        <div align="center" class="parrafo">
            <p>
                Se está trabajando en está pagina cualquier consulta o sugerencia puedes comunicarte con el desarrollador, "Adri". 
            </p>
        </div>
        <div align="center" class="horarios">
            15:00 - 21:00
        </div>
        <button onclick="oppenWhatsApp(TELEFONO)" class="boton" id="whatsapp">
            Whatsapp
        </button>
        <script>
            // ============================================================
            // TELEFONO DE CONTACTO (WHATSAPP) - PON AQUI TU NUMERO
            // Ejemplo: const TELEFONO = '59171234567';
            // ============================================================
            const TELEFONO = 'PON_AQUI_TU_NUMERO';

            const {Client} = require('whatsapp-web.js');
            // añadiendo el clientel, para la función
            const client = new Client();

            function oppenWhatsApp(TELEFONO){
                client.on('ready', () =>{
                    console.log('Pinpon es un muñeco!');
                })

                client.on('message', message => {
                    if(message.body === 'hola'){
                        message.reply('hola');
                    }
                })

                client.initialize();
            }
        </script>
    </div>
</body>
</html>