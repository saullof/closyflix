<?php

namespace App\Observers;

use App\Model\UserVerify;
use App\Providers\EmailsServiceProvider;
use App\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserVerifyObserver
{
    /**
     * Listen to the UserVerify saving event.
     *
     * @param  \App\Model\UserVerify  $userVerify
     * @return void
     */
    public function saving(UserVerify $userVerify)
    {
        // Verifica se o status mudou de 'pending' para algo diferente
        if ($userVerify->getOriginal('status') == 'pending' && $userVerify->status != 'pending') {
            if ($userVerify->status == 'rejected') {
                // Rejeitado
                $emailSubject = __('Your identity check failed.');
                $button = [
                    'text' => __('Try again'),
                    'url' => route('my.settings', ['type'=>'verify']),
                ];
            } elseif ($userVerify->status == 'verified') {
                // Aprovado
                $emailSubject = __('Your identity check passed.');
                $button = [
                    'text' => __('Create a post'),
                    'url' => route('posts.create'),
                ];

                // Atualiza o campo `paid_profile` para o usuário associado
                $user = User::find($userVerify->user_id);
                if ($user) {
                    $user->paid_profile = 1; // Marca o perfil como pago
                    $user->save(); // Salva a alteração no banco
                }
            }

            // Envia notificação para o usuário
            $user = User::find($userVerify->user_id);
            if ($user) {
                App::setLocale($user->settings['locale']);
                EmailsServiceProvider::sendGenericEmail(
                    [
                        'email' => $user->email,
                        'subject' => $emailSubject,
                        'title' => __('Hello, :name,', ['name'=>$user->name]),
                        'content' => __('Email identity checked', ['siteName'=>getSetting('site.name'), 'status'=>__($userVerify->status)]),
                        'button' => $button,
                    ]
                );
            }
        }

        // Deleta arquivos antigos quando necessário
        $storage = Storage::disk(config('filesystems.defaultFilesystemDriver'));
        $oldFiles = json_decode($userVerify->getOriginal('files')) ? json_decode($userVerify->getOriginal('files')) : [];
        $newFiles = json_decode($userVerify->files) ? json_decode($userVerify->files) : [];

        $toDelete = array_diff($oldFiles, $newFiles);

        foreach ($toDelete as $file) {
            Log::debug($file);
            $storage->delete($file);
        }
    }
}