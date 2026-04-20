@once
    <link href="https://vjs.zencdn.net/7.14.3/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/7.14.3/video.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
@endonce

@php
    // Define o domínio do CDN e do link direto
    $cdnUrl = 'https://closyflix.nyc3.cdn.digitaloceanspaces.com';
    $directUrl = 'https://closyflix.nyc3.digitaloceanspaces.com';

    // Substitui o domínio direto pelo CDN
    $videoPath = str_replace($directUrl, $cdnUrl, $attachment->path);

    // Verifica se o vídeo é no formato HLS (.m3u8)
    $isHls = strpos($videoPath, '.m3u8') !== false;
    if ($isHls) {
        $videoPath = str_replace('/hls.m3u8', '/hls.m3u8/master.m3u8', $videoPath);
    }

    // Caminho para a thumbnail
    $thumbnailPath = pathinfo($attachment->path, PATHINFO_FILENAME);

    $isLocked = $isLocked ?? false;
    $hasThumbnail = (bool) ($attachment->has_thumbnail ?? false);
    $thumbnailUrl = $hasThumbnail ? $attachment->thumbnail : null;
    $blurBackgroundUrl = $thumbnailUrl ?? $videoPath;
    $displayImageUrl = $isLocked && $thumbnailUrl ? $thumbnailUrl : $videoPath;
@endphp
 
 @if($isGallery)
    @if(AttachmentHelper::getAttachmentType($attachment->type) == 'image')
        <div class="card post-image-container position-relative w-100 h-100 d-flex justify-content-center align-items-center">
            <div class="image-background-{{$attachment->id}}" style="background-size: cover; background-position: center; filter: blur(10px) brightness(0.6);"></div>
            <div style="overflow: hidden" class="image-container overflow-hidden w-100 h-100 rounded-0">
                <img src="{{$displayImageUrl}}" draggable="false" alt="" loading="lazy" decoding="async">
            </div>
        </div>

    @elseif(AttachmentHelper::getAttachmentType($attachment->type) == 'video')
        <div class="video-container position-relative w-100 h-100 d-flex justify-content-center align-items-center">
            <!-- Fundo desfocado -->
            <div class="video-background-{{$attachment->id}} teste1" style="background-image: url('{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}'); background-size: cover; background-position: center; filter: blur(10px) brightness(0.6);"></div>

            @if($isHls)
                <!-- Thumbnail e Vídeo HLS -->
                <div id="thumbnail-wrapper-{{$attachment->id}}" class="image-container position-relative overflow-hidden w-100 h-100 image-containerCss">
                    <img id="thumbnail-{{$attachment->id}}" src="{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}" alt="thumbnail" loading="lazy" decoding="async">
                    <div class="play-button position-absolute top-50 left-50 translate-middle">
                        <img src="{{ asset('img/IconeRep.png') }}" alt="Play" style="width: 50px; height: 50px;">
                    </div>
                </div>

                <video id="hls-player-{{$attachment->id}}" class="video-js vjs-default-skin w-100" controls style="display: none;" poster="{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}">
                    <!-- Nenhuma fonte é definida inicialmente para o vídeo -->
                </video>

                <script>
                    document.getElementById('thumbnail-wrapper-{{$attachment->id}}').addEventListener('click', function() {
                        var thumbnailWrapper = document.getElementById('thumbnail-wrapper-{{$attachment->id}}');
                        var video = document.getElementById('hls-player-{{$attachment->id}}');

                        // Esconde a thumbnail e mostra o vídeo
                        thumbnailWrapper.style.display = 'none';
                        video.style.display = 'block';

                        // Atribui o src do vídeo somente após o clique
                        if (video.canPlayType('application/vnd.apple.mpegurl')) {
                            video.src = "{{$videoPath}}";
                        } else if (Hls.isSupported()) {
                            var hls = new Hls();
                            hls.loadSource("{{$videoPath}}");
                            hls.attachMedia(video);
                        } else {
                            console.error('HLS não suportado neste navegador.');
                        }

                        // Reproduz o vídeo após o carregamento
                        video.play();
                    });
                </script>
            @else
                {{-- Fallback para MP4 --}}
                <div id="thumbnail-wrapper-{{$attachment->id}}" class="image-container position-relative overflow-hidden w-100 h-100 image-containerCss">
                    <img id="thumbnail-{{$attachment->id}}" src="{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}" alt="thumbnail" loading="lazy" decoding="async">
                    <div class="play-button position-absolute top-50 left-50 translate-middle">
                        <img src="{{ asset('img/IconeRep.png') }}" alt="Play" style="width: 50px; height: 50px;">
                    </div>
                </div>

                <!-- O vídeo inicialmente fica escondido -->
                <video id="mp4-player-{{$attachment->id}}" class="video-preview w-100" controls controlsList="nodownload" style="display: none;" poster="{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}">
                    <source src="{{$videoPath}}#t=0.001" type="video/mp4">
                </video>

                <script>
                    document.getElementById('thumbnail-wrapper-{{$attachment->id}}').addEventListener('click', function() {
                        var thumbnailWrapper = document.getElementById('thumbnail-wrapper-{{$attachment->id}}');
                        var video = document.getElementById('mp4-player-{{$attachment->id}}');

                        // Esconde a thumbnail e mostra o vídeo
                        thumbnailWrapper.style.display = 'none';
                        video.style.display = 'block';

                        // Reproduz o vídeo
                        video.play();
                    });
                </script>
            @endif
        </div>
    @elseif(AttachmentHelper::getAttachmentType($attachment->type) == 'audio')
        <div class="audio-wrapper h-100 w-100 d-flex justify-content-center align-items-center">
            <audio class="audio-preview w-75" src="{{$videoPath}}#t=0.001" controls controlsList="nodownload"></audio>
        </div>
    @endif
@else
    @if(AttachmentHelper::getAttachmentType($attachment->type) == 'image')
        <div class="position-relative w-100 d-flex justify-content-center align-items-center">
            <div style="aspect-ratio: 1/1" class="image-container post-image-horizontal position-relative overflow-hidden w-100 h-100 rounded-0 teste2">
                <div class="image-background-{{$attachment->id}}" style="background-size: cover; background-position: center; filter: blur(10px) brightness(0.6);"></div>
                <img src="{{$displayImageUrl}}" draggable="false" alt="" loading="lazy" decoding="async">
            </div>
        </div>
        {{--  <img src="{{$videoPath}}" draggable="false" alt="" class="img-fluid rounded-0 w-100">  --}}
    @elseif(AttachmentHelper::getAttachmentType($attachment->type) == 'video')
    
        <div class="video-wrapper h-100 w-100 d-flex justify-content-center align-items-center">
        <div class="video-container position-relative w-100 h-100 d-flex justify-content-center align-items-center">  
            <div class="image-background removebackgroundimg2 backgroundimg2" style="background-image: url('{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}'); background-size: cover; background-position: center; filter: blur(10px) brightness(0.6);"></div>
        
                @if($isHls)
                    {{-- Prioriza HLS --}}
                    <div id="thumbnail-wrapper-{{$attachment->id}}" class="image-container position-relative overflow-hidden w-100 h-100 image-containerCss">
                        <img id="thumbnail-{{$attachment->id}}" src="{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}" class="img-fluid h-100 w-100 object-cover" alt="thumbnail" loading="lazy" decoding="async">
                        <div class="play-button position-absolute top-50 left-50 translate-middle">
                            <img src="{{ asset('img/IconeRep.png') }}" alt="Play" style="width: 50px; height: 50px;">
                        </div>
                    </div>

                    <video id="hls-player-{{$attachment->id}}" class="video-js vjs-default-skin w-100" controls style="display: none;"></video>
                    <script>
                        document.getElementById('thumbnail-wrapper-{{$attachment->id}}').addEventListener('click', function() {
                            var thumbnailWrapper = document.getElementById('thumbnail-wrapper-{{$attachment->id}}');
                            var video = document.getElementById('hls-player-{{$attachment->id}}');

                            thumbnailWrapper.style.display = 'none';
                            video.style.display = 'block';

                            if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                video.src = "{{$videoPath}}";
                            } else if (Hls.isSupported()) {
                                var hls = new Hls();
                                hls.loadSource("{{$videoPath}}");
                                hls.attachMedia(video);
                            }

                            video.play();
                        });
                    </script>
                @else
                    {{-- Fallback para MP4 --}}
                    <div id="thumbnail-wrapper-{{$attachment->id}}" class="image-container position-relative overflow-hidden w-100 h-100 image-containerCss" style="z-index: 1;">
                        <img id="thumbnail-{{$attachment->id}}" src="{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}" class="img-fluid h-100 w-100 object-cover" alt="thumbnail" loading="lazy" decoding="async">
                        <div class="play-button position-absolute top-50 left-50 translate-middle">
                            <img src="{{ asset('img/IconeRep.png') }}" alt="Play" style="width: 50px; height: 50px;">
                        </div>
                    </div>



                    
                    <video id="mp4-player-{{$attachment->id}}" class="video-preview w-100" controls controlsList="nodownload" style="display: none; z-index: 2;" poster="{{ $thumbnailUrl ?? Storage::url('posts/videos/thumbnails/' . $thumbnailPath . '.jpg') }}">
                        <source src="{{$videoPath}}#t=0.001" type="video/mp4">
                    </video>

                    <script>
                        document.getElementById('thumbnail-wrapper-{{$attachment->id}}').addEventListener('click', function() {
                            var thumbnailWrapper = document.getElementById('thumbnail-wrapper-{{$attachment->id}}');
                            var video = document.getElementById('mp4-player-{{$attachment->id}}');

                            thumbnailWrapper.style.display = 'none';
                            video.style.display = 'block';

                            video.play();
                        });
                    </script>
                @endif
            </div>
        </div>
    @elseif(AttachmentHelper::getAttachmentType($attachment->type) == 'audio')
        <div class="audio-wrapper h-100 w-100 d-flex justify-content-center align-items-center">
            <audio class="audio-preview w-75" src="{{$videoPath}}#t=0.001" controls controlsList="nodownload"></audio>
        </div>
    @endif
@endif



<style>
    .video-wrapper {
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        align-items: center; /* Centraliza o conteúdo verticalmente */
        justify-content: center; /* Centraliza o conteúdo horizontalmente */
        background-color: black; /* Adiciona um fundo para contraste */
    }

    .image-container {
        position: relative;
        overflow: hidden;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center; /* Centraliza verticalmente */
        justify-content: center; /* Centraliza horizontalmente */
    }

    .image-background-{{$attachment->id}} {
        background-image: url('{{$blurBackgroundUrl}}');
        background-size: cover;
        background-position: center;
        filter: blur(10px) brightness(0.6);
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    }

    .image-background {
        background-image: url('{{$blurBackgroundUrl}}');
        background-size: cover;
        background-position: center;
        filter: blur(10px) brightness(0.6);
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
    }

    .image-container img {
        z-index: 2;
        width: 100%;
        height: 100%;
        object-fit: contain; /* Garante que a imagem se ajuste sem cortar */
    }

    .video-container {
        position: relative;
        overflow: hidden;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center; /* Centraliza verticalmente */
        justify-content: center; /* Centraliza horizontalmente */
        aspect-ratio: 1 / 1;
    }

    .video-thumbnail-container {
        position: relative;
        z-index: 2;
    }

    .video-element {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Ajusta o vídeo ao container sem cortar */
        z-index: 2;
    }

    .video-background-{{$attachment->id}} {
        background-image: url('{{ Storage::url("posts/videos/thumbnails/" . $thumbnailPath . ".jpg") }}');
        background-size: cover;
        background-position: center;
        filter: blur(10px) brightness(0.6);
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }

    .video-preview {
        width: 100%;
        height: auto;
        max-height: 100%; /* Limita a altura para evitar cortes verticais */
        object-fit: contain; /* Ajusta o vídeo sem cortar, mantendo a proporção */
    }

    .video-thumbnail {
        object-fit: contain; /* Ajusta a imagem da miniatura sem cortar */
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
    }

    .thumb-wrapper {
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        align-items: center; /* Centraliza verticalmente */
        justify-content: center; /* Centraliza horizontalmente */
        background-color: black; /* Fundo para contraste */
    }

    .thumb-preview {
        width: 100%;
        height: auto;
        max-height: 100%; /* Limita a altura para evitar cortes verticais */
        object-fit: contain; /* Ajusta a thumb sem cortar, mantendo a proporção */
    }

    /* Estilo adicional para a play button */
    .play-button {
        position: absolute; /* Necessário para usar o top/left */
        top: 50%; /* Centraliza verticalmente */
        left: 50%; /* Centraliza horizontalmente */
        transform: translate(-50%, -50%); /* Ajuste para centralizar exatamente no meio */
        z-index: 10; /* Certifique-se de que está acima de outros elementos */
        display: flex; /* Facilita o alinhamento do conteúdo interno */
        justify-content: center;
        align-items: center;
        pointer-events: none; /* Evita conflitos de clique com o vídeo */
    }

    .play-button img {
        width: 50px; /* Tamanho do ícone */
        height: 50px; /* Tamanho do ícone */
        pointer-events: auto; /* Permite interação apenas no ícone */
        cursor: pointer; /* Mostra o cursor clicável */
    }

    .play-button {
        background: rgba(0, 0, 0, 0.5); /* Fundo transparente */
        border-radius: 50%; /* Forma circular ao redor do ícone */
        padding: 10px;
    }


    /* Adiciona bordas arredondadas ao redor das imagens */
    .img-fluid {
        border-radius: 8px;
    }

    .backgroundimg2 {
        z-index: 1;
    }

    .backgroundvideo2 {
        z-index: 2;
    }


    

</style>
