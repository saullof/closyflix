@if(!Auth::user()->email_verified_at) @include('elements.resend-verification-email-box') @endif
<style type="text/css" media="screen">
    .ace_editor {
        min-height: 200px !important;
        width: 100%;
        overflow: hidden;
    font: 12px / normal 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', 'source-code-pro', monospace;
    direction: ltr;
    text-align: left;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
    }
	.btn{
        margin-top: 20px;
    }
</style>

<form id="pixel-form" method="POST" action="{{route('my.settings.pixel.save')}}">
    @csrf
    @if(session('success'))
        <div class="alert alert-success text-white font-weight-bold mt-2" role="alert">
            {{session('success')}}
            <button type="button" class="close" data-dismiss="alert" aria-label="{{__('Close')}}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    
    <div class="form-group">
        <label for="meta-head">Meta ( Pixel Id )</label>
        <input class="form-control" value="{{ e($pixel_data['meta-head'] ?? '') }}" id="meta-head" name="meta-head" type="text">
    </div>


    <div class="form-group">
        <label for="google-head">Google ( Measurement Id )</label>
        <input class="form-control" value="{!! $pixel_data['google-head'] !!}" id="google-head" name="google-head" type="text">
    </div>

    <div class="form-group">
        <label for="tiktok-head">Tiktok ( Pixel Id )</label>
        <input class="form-control" value="{!! $pixel_data['tiktok-head'] !!}" id="tiktok-head" name="tiktok-head" type="text">
    </div>

    <div class="form-group">
        <label for="twitter-head">Twitter ( Pixel Id )</label>
        <input class="form-control" value="{!! $pixel_data['twitter-head'] !!}" id="twitter-head" name="twitter-head" type="text">
    </div>

</form>

<button class="btn btn-primary btn-block rounded mr-0" onclick="savePixel()">{{__('Save')}}</button>
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js" type="text/javascript" charset="utf-8"></script>

  <script>
      function savePixel(){
        const form = document.getElementById("pixel-form");

        form.submit();
      }
  </script>