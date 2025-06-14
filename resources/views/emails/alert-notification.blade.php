@component('mail::message')
# {{ $notification->title }}

Olá {{ $user->name }},

{{ $notification->message }}

@if($alert)
## Detalhes do Alerta

- **Tipo:** {{ ucfirst($alert->type) }}
- **Condição:** {{ $alert->condition }}
- **Valor:** {{ $alert->value }}
- **Ativo:** {{ $alert->asset ? $alert->asset->symbol : 'N/A' }}
@endif

@component('mail::button', ['url' => config('app.url') . '/alerts/' . $alert->id])
Ver Detalhes
@endcomponent

Atenciosamente,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Se você não esperava receber este email, por favor ignore-o ou entre em contato com nosso suporte.
@endcomponent
@endcomponent
