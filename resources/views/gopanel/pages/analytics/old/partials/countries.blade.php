<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Ölkələr</h4>
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">№</th>
                                <th class="align-middle">Bayraq</th>
                                <th class="align-middle">Ölkə adı</th>
                                <th class="align-middle">İSO KODU</th>
                                <th class="align-middle">Toplam Giriş sayı</th>
                                <th class="align-middle">İlk giriş</th>
                                <th class="align-middle">Son giriş</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($countries as $country)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td><img src="{{$country->flag}}" alt=""></td>
                                    <td>{{$country->name}}</td>
                                    <td>{{$country->iso_code}}</td>
                                    <td>{{$country->hit_count}}</td>
                                    <td>{{$country->first_visited_at}}</td>
                                    <td>{{$country->first_visited_at}}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Heç bir məlumat tapılmadı
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- end table-responsive -->
            </div>
        </div>
    </div>
</div>