<div id="notificationContainer"></div>
@if(session('success'))
<script>document.addEventListener('DOMContentLoaded', function(){ showNotification("{{ session('success') }}", 'success'); });</script>
@endif
@if(session('error'))
<script>document.addEventListener('DOMContentLoaded', function(){ showNotification("{{ session('error') }}", 'error'); });</script>
@endif
@if(session('warning'))
<script>document.addEventListener('DOMContentLoaded', function(){ showNotification("{{ session('warning') }}", 'warning'); });</script>
@endif
@if(session('info'))
<script>document.addEventListener('DOMContentLoaded', function(){ showNotification("{{ session('info') }}", 'info'); });</script>
@endif