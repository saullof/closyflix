<!-- DESCOMENTAR
<div class="mt-3 inline-border-tabs text-bold">
    <nav class="nav nav-pills nav-justified">
        @foreach($availableSettings as $route => $setting)
            <a class="nav-item nav-link {{$activeSettingsTab == $route ? 'active' : ''}}" href="{{route('my.settings',['type'=>$route])}}">
                <div class="d-flex justify-content-center">
                    @include('elements.icon',['icon'=>$setting['icon'].'-outline','centered'=>'false','variant'=>'medium'])
                </div>
            </a>
        @endforeach
    </nav>
</div>-->


<style>
.mt-3.inline-border-tabs {
    overflow-x: auto !important;
    white-space: nowrap !important;
}

.nav {
    display: flex !important;
    flex-wrap: nowrap !important;
    justify-content: flex-start !important;
    overflow-x: auto !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}
#e93745
.nav-item {
    flex-shrink: 0 !important;
    min-width: 80px !important; /* Ajuste conforme necessário */
    text-align: center !important;
}

.nav-pills .nav-link {
    padding: 0.5rem 1rem !important; /* Certifique-se que o padding não está comprimindo os itens */
}

</style>