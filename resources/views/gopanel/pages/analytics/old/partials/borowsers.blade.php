<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Brauzerlər</h4>
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="align-middle">№</th>
                                <th class="align-middle">Ikon</th>
                                <th class="align-middle">Brauzer</th>
                                <th class="align-middle">Toplam Giriş sayı</th>
                                <th class="align-middle">İlk giriş</th>
                                <th class="align-middle">Son giriş</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($borowsers as $borowser)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td><img src="{{$borowser?->icon}}" alt=""></td>
                                    <td>{{$borowser->name}}</td>
                                    <td>{{$borowser->hit_count}}</td>
                                    <td>{{$borowser->first_visited_at}}</td>
                                    <td>{{$borowser->first_visited_at}}</td>
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