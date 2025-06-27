<div>
    <ul class="list-group list-group-unbordered">
        <li class="list-group-item">
            <b>İstifadəçi :</b> <span class="pull-right">{{$log?->user_name}}</span>
        </li>
        <li class="list-group-item">
            <b>Admin :</b> <span class="pull-right">{{$log?->admin_name}}</span>
        </li>
        <li class="list-group-item">
            <b>Kanal :</b> <span class="pull-right">{{$log?->channel}}</span>
        </li>
        <li class="list-group-item">
            <b>Səviyyə :</b> <span class="pull-right">{{$log?->level}}</span>
        </li>
        <li class="list-group-item">
            <b>Mesaj :</b> <span class="pull-right">{{$log?->message}}</span>
        </li>
        <li class="list-group-item">
            <b>context :</b>  <br>
            <div class="context">
                @json($log?->context)
            </div>
        </li>
        <li class="list-group-item">
            <b>log_details :</b> <br>
            <div class="log_details">
                @json($log?->log_details)
            </div>
        </li>
    </ul>
</div>