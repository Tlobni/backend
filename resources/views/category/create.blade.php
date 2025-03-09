@extends('layouts.main')
@section('title')
    {{__("Create Categories")}}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('category.index') }}">< {{__("Back to All Categories")}} </a>
        </div>
        <div class="row">
            <form action="{{ route('category.store') }}" method="POST" data-parsley-validate enctype="multipart/form-data">
                @csrf
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">{{__("Add Category")}}</div>

                        <div class="card-body mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="category_name" class="mandatory form-label">{{ __('Name') }}</label>
                                        <input type="text" name="name" id="category_name" class="form-control" data-parsley-required="true">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="category_slug" class="form-label">{{ __('Slug') }} <small>{{__('(English Only)')}}</small></label>
                                        <input type="text" name="slug" id="category_slug" class="form-control" data-parsley-required="true">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="category_type" class="mandatory form-label">{{ __('Category Type') }}</label>
                                        <select name="type" id="category_type" class="form-select form-control" data-parsley-required="true" onchange="loadParentCategories()">
                                            <option value="">{{ __('Select Type') }}</option>
                                            <option value="service_experience">{{ __('Service & Experience') }}</option>
                                            <option value="providers">{{ __('Providers') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="col-md-12 form-group">
                                        <label for="p_category" class="form-label">{{ __('Parent Category') }}</label>
                                        <select name="parent_category_id" id="p_category" class="form-select form-control" data-placeholder="{{__("Select Category")}}">
                                            <option value="">{{__("Select a Category")}}</option>
                                            <!-- Parent categories will be loaded dynamically based on selected type -->
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="col-md-12 form-group mandatory">
                                        <label for="Field Name" class="mandatory form-label">{{ __('Image') }}</label>
                                        <input type="file" name="image" id="image" class="form-control" data-parsley-required="true" accept=".jpg,.jpeg,.png">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="description" class="mandatory form-label">{{ __('Description') }}</label>
                                    <textarea name="description" id="description" class="form-control" cols="10" rows="5"></textarea>
                                    <div class="form-check form-switch mt-3">
                                        <input type="hidden" name="status" id="status" value="0">
                                        <input class="form-check-input status-switch" type="checkbox" role="switch" aria-label="status">{{ __('Active') }}
                                        <label class="form-check-label" for="status"></label>
                                    </div>
                                </div>


                                @if($languages->isNotEmpty())
                                <div class="row">
                                    <hr>
                                    <h5>{{ __("Translation") . " " . __("Optional") }}</h5>

                                    @foreach($languages as $key => $language)
                                        <div class="col-md-6 form-group">
                                            <label for="name_{{$language->id}}" class="form-label">{{ ($key + 1) . ". " . $language->name }}:</label>
                                            <input name="translations[{{$language->id}}]" id="name_{{$language->id}}" class="form-control" value="">
                                        </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 text-end">
                        <input type="submit" class="btn btn-primary" value="{{__("Save and Back")}}">
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

<script>
    function loadParentCategories() {
        const typeSelect = document.getElementById('category_type');
        const parentCategorySelect = document.getElementById('p_category');
        const selectedType = typeSelect.value;
        
        // Clear current options except the first one
        while (parentCategorySelect.options.length > 1) {
            parentCategorySelect.remove(1);
        }
        
        // If no type is selected, return
        if (!selectedType) {
            console.log('No type selected');
            return;
        }
        
        console.log('Loading parent categories for type:', selectedType);
        
        // Use fetch API
        fetch('{{ url("category/get-parent-categories") }}?type=' + selectedType)
            .then(response => response.json())
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Add options for each parent category
                    data.categories.forEach(function(category) {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        
                        // Add indentation based on level
                        if (category.level > 0) {
                            option.textContent = '- '.repeat(category.level) + option.textContent;
                        }
                        
                        parentCategorySelect.appendChild(option);
                    });
                    
                    if (data.categories.length === 0) {
                        console.log('No parent categories found for type:', selectedType);
                    }
                } else {
                    console.error('Error from server:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading parent categories:', error);
            });
    }
</script>

@push('scripts')
<!-- Keep this empty to avoid conflicts -->
@endpush


