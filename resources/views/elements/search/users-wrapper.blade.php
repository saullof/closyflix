@if(count($posts))
    @foreach($users as $user)
        @include('elements.search.users-list-element',['user'=>$user])
    @endforeach
@else
    <h5 class="text-center mb-2 mt-2">{{__('No users were found')}}</h5>
@endif

<script>
        document.addEventListener('DOMContentLoaded', function() {
            const profiles = document.querySelectorAll('.perfilCard .background-image');
            profiles.forEach(function(profile) {
                const backgroundImage = profile.style.backgroundImage;
                if (backgroundImage.includes('https://closyflix.com//img/default-avatar.jpg')) {
                    profile.closest('.perfilCard').style.display = 'none';
                }
            });
        });

    </script>