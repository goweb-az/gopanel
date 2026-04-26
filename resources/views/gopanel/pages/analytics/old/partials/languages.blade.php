<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Dillər</h4>
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">№</th>
                                <th class="align-middle">Ikon</th>
                                <th class="align-middle">Kod</th>
                                <th class="align-middle">Dil</th>
                                <th class="align-middle">Toplam Giriş sayı</th>
                                <th class="align-middle">İlk giriş</th>
                                <th class="align-middle">Son giriş</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($analytics_languages as $language)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td><img src="{{$language?->flag}}" width="30"></td>
                                    <td>{{$language->code}}</td>
                                    <td>{{$language->name}}</td>
                                    <td>{{$language->hit_count}}</td>
                                    <td>{{$language->first_visited_at}}</td>
                                    <td>{{$language->first_visited_at}}</td>
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