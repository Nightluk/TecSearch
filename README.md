# made by: 
<a href=https://github.com/Nightluk>@Nightluk</a>,
<a href=https://github.com/LucasCintraReis>@LucasCintraReis</a>,
<a href=https://github.com/vittoretti>@vittoretti</a>,
<a href=https://github.com/Guima08/Rpg>@Guima08</a>,
<a href=https://github.com/Yolawzera>@Pedro08</a>


## Como rodar

1. No **SQL Editor do Supabase** rode `banco/ajustes.sql` (ativa RLS, telefone único, nota 1–5, composite unique em avaliações e categorias iniciais).
2. Copie `.env.example` para `.env` e preencha com a *Session pooler connection string* do Supabase.
3. Requer PHP 8.1+ com `pdo_pgsql`. Rode `php -S localhost:8000` dentro da pasta e abra http://localhost:8000.

`banco/schema_referencia.sql` é só a cópia do schema para testes locais (não rode no Supabase).
