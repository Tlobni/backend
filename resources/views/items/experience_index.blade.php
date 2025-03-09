@extends('layouts.main')

@section('title')
    {{ __('Experience Item Management') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-body">
                <table class="table-borderless table-striped" aria-describedby="mydesc" id="table_list"
                       data-toggle="table" data-url="{{ route('experience.items.data') }}" data-click-to-select="true"
                       data-side-pagination="server" data-pagination="true"
                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                       data-search-align="right" data-toolbar="#toolbar" data-show-columns="true"
                       data-show-refresh="true" data-fixed-columns="true" data-fixed-number="1"
                       data-fixed-right-number="1" data-trim-on-search="false" data-responsive="true"
                       data-sort-name="id" data-sort-order="desc" data-pagination-successively-size="3"
                       data-escape="true"
                       data-query-params="queryParams" data-table="items">
                    <thead class="thead-dark">
                    <tr>
                        <th scope="col" data-field="id" data-align="center" data-sortable="true">{{ __('ID') }}</th>
                        <th scope="col" data-field="name" data-align="center" data-sortable="true">{{ __('Name') }}</th>
                        <th scope="col" data-field="description" data-align="center" data-sortable="true">{{ __('Description') }}</th>
                        <th scope="col" data-field="user.name" data-align="center" data-sortable="true">{{ __('User') }}</th>
                        <th scope="col" data-field="price" data-align="center" data-sortable="true">{{ __('Price') }}</th>
                        <th scope="col" data-field="image" data-formatter="imageFormatter" data-align="center">{{ __('Image') }}</th>
                        <th scope="col" data-field="gallery_images" data-formatter="galleryImagesFormatter" data-align="center">{{ __('Other Images') }}</th>
                        <th scope="col" data-field="country" data-align="center" data-sortable="true">{{ __('Country') }}</th>
                        <th scope="col" data-field="state" data-align="center" data-sortable="true">{{ __('State') }}</th>
                        <th scope="col" data-field="city" data-align="center" data-sortable="true">{{ __('City') }}</th>
                        <th scope="col" data-field="featured_items" data-formatter="featuredFormatter" data-align="center">{{ __('Featured/Premium') }}</th>
                        <th scope="col" data-field="status" data-align="center" data-sortable="true">{{ __('Status') }}</th>
                        <th scope="col" data-field="active_status" data-formatter="activeStatusFormatter" data-align="center">{{ __('Active') }}</th>
                        <th scope="col" data-field="expiry_date" data-align="center" data-sortable="true">{{ __('Expiry Date') }}</th>
                        <th scope="col" data-field="operate" data-escape="false" data-align="center" data-sortable="false">{{ __('Action') }}</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>
@endsection

@section('js')
<script>
    function imageFormatter(value, row) {
        if (value) {
            return '<img src="' + value + '" alt="Item Image" class="img-thumbnail" style="max-width: 80px;">';
        }
        return '-';
    }
    
    function galleryImagesFormatter(value, row) {
        if (value && value.length > 0) {
            let html = '';
            for (let i = 0; i < Math.min(value.length, 2); i++) {
                html += '<img src="' + value[i].image + '" alt="Gallery Image" class="img-thumbnail" style="max-width: 50px; margin: 2px;">';
            }
            return html;
        }
        return '-';
    }
    
    function featuredFormatter(value, row) {
        if (row.show_only_to_premium) {
            return '<span class="badge bg-primary">Premium</span>';
        }
        return '-';
    }
    
    function activeStatusFormatter(value, row) {
        if (value) {
            return '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked disabled></div>';
        }
        return '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" disabled></div>';
    }
    
    function queryParams(params) {
        return params;
    }
</script>
@endsection 