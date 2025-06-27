<div>
    <ul class="list-group list-group-unbordered">
        <li class="list-group-item">
            <b>Əməliyyat :</b> <span class="pull-right">{{$history?->event_name}}</span>
        </li>
        <li class="list-group-item">
            <b>Kim tərəfindən :</b> <span class="pull-right">{{$history?->causerName}}</span>
        </li>
        <li class="list-group-item">
            <b>Məlumat :</b> <span class="pull-right">{{$history?->description}}</span>
        </li>
        <li class="list-group-item">
            <b>Mode :</b> <span class="pull-right">{{$history?->log_name}}</span>
        </li>
        <li class="list-group-item">
            <b>Tarix :</b> <span class="pull-right">{{$history?->created_at}}</span>
        </li>
        <li class="list-group-item">
            <b>Dəyərlər :</b> <br>
            <div class="properties">
                @json($history?->properties)
            </div>
        </li>
    </ul>
</div>