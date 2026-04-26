<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Toplam Kliklər</h4>
                @include('gopanel.component.datatable',[
                    '__datatableName' => 'gopanel.Seo.AnalyticsLink',
                    '__datatableId' => 'AnalyticsLink'
                ])
                <!-- end table-responsive -->
            </div>
        </div>
    </div>
</div>