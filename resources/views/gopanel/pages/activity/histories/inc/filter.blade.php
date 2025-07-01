<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <form action='{{ route("gopanel.activity.history.index", $_GET)}}' method="GET" >

                    @if (count($_GET))
                    @foreach ($_GET as $key => $value)
                    @if (!in_array($key,['event','from','to']))
                        <input type="hidden" name="{{$key}}" value="{{$value}}">
                    @endif
                    @endforeach
                    @endif

                    <div class="row">
                        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 mb-3">
                            <div class="form_group">
                                <label class="form-label" for="event">Əməliyyat</label>
                                <select class="form-select" name="event" id="event">
                                    <option value="">Bütün Əməliyyatlar</option>
                                    @foreach ($events as $key => $eventItem)
                                        <option value="{{$key}}" @selected($key == $event)>{{ucfirst($eventItem)}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 mb-3">
                            <label for="from_date" class="form-label">Başlama</label>
                            <input type="date" class="form-control" name="from" id="from_date" placeholder="" value="{{$from}}">
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 mb-3">
                            <label for="from_date" class="form-label">Bitmə</label>
                            <input type="date" class="form-control" name="to" id="from_date" placeholder="" value="{{$to}}">
                        </div>

                        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3 filter-btn-div">
                            <button type="submit" class="btn btn-success filter-btn" >
                                <i class="fa fa-search"></i> Axtar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div><!--end col-->
</div>