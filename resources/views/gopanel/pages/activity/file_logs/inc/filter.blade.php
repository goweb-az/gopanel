<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <form action='{{ route("gopanel.activity.file-logs.index", $_GET)}}' method="GET" >

                    @if (count($_GET))
                    @foreach ($_GET as $key => $value)
                    @if (!in_array($key,['level','from','to','channel']))
                        <input type="hidden" name="{{$key}}" value="{{$value}}">
                    @endif
                    @endforeach
                    @endif

                    <div class="row">
                        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3">
                            <div class="form_group">
                                <label class="form-label" for="level">Səviyyə</label>
                                <select class="form-select" name="level" id="level">
                                    <option value="">Bütün Səviyyələr</option>
                                    @foreach ($levels as $levelItem)
                                        <option value="{{$levelItem}}" @selected($levelItem == $level)>{{ucfirst($levelItem)}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mb-3">
                            <label class="form-label" for="channel">Kanal</label>
                            <select name="channel" class="form-select" id="channel">
                                <option value="">Bütün Kanallar</option>
                                @foreach ($channels as $key => $channelItem)
                                    <option value="{{$key}}" @selected($channel == $key)>{{ucfirst($channelItem['name'])}}</option>
                                @endforeach
                            </select>
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