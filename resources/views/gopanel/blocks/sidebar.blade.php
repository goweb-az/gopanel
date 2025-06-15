<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">

                @foreach ($sidebarItems as $item)
                    @if (!$item->get('route') and $item->get('inner') === null)
                        <li class="menu-title" key="t-menu">{!! $item['title'] !!}</li>
                    @elseif($item->get('route') and !is_array($item->get('inner')))
                        <li class="{{ $item->get('is_active_route') ? 'mm-active' : '' }}">
                            <a  @if ($item->get('target') == '_blank') target="_blank" @endif href="{{ route($item->get('route'), $item->get('params') ?? []) }}">
                                <span class="pc-micon">{!! $item->get('icon') !!}</span>
                                <span>{{ $item->get('title') }}</span>
                                @if (!is_null($item->get("badge")))
                                    <span class="pc-badge">{!!$item->get("badge")!!}</span>
                                @endif
                            </a>
                        </li>
                    @else
                        <li class="{{ $item->get('is_active_route') ? 'mm-active' : '' }}">
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                @if (!is_null($item->get('icon')))
                                <span class="pc-micon">
                                    {!! $item->get('icon') !!}
                                </span>
                                @endif
                                <span class="pc-mtext">{!! $item->get('title') !!}</span>
                                <span class="pc-arrow"><i data-feather="chevron-right"></i></span>
                                @if (!is_null($item->get("badge")))
                                    {!!$item->get("badge")!!}
                                @endif
                            </a>
                            <ul class="sub-menu">
                                @foreach ($item->get('inner') as $inner)
                                    {{-- @if(!sidebarGuardCheck($inner->get('guards')))
                                        @continue
                                    @endif --}}
                                    <li>
                                        <a href="{{ route($inner->get('route')) }}" class="pc-link {{ $inner->get('isActiveGroup') ? 'active' : '' }}">
                                            @if ($inner->has("icon"))
                                            <span class="mm-inner-icon">
                                                {!! $inner->get('icon') !!}
                                            </span> 
                                            @endif
                                            {!! $inner->get('title') !!}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @endforeach

                

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>