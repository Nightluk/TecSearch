-- Rode UMA vez no SQL Editor do Supabase. Pode rodar de novo sem problema (é idempotente).

-- 1) Segurança: sem RLS, a chave "anon" do Supabase consegue ler a tabela clientes (com as senhas).
--    O PHP conecta direto pelo Postgres (role postgres) e ignora o RLS, então o app continua funcionando.
alter table clientes               enable row level security;
alter table assistencias           enable row level security;
alter table assistencia_fotos      enable row level security;
alter table categorias_aparelhos   enable row level security;
alter table assistencia_categorias enable row level security;
alter table avaliacoes             enable row level security;

-- 2) Integridade: login por telefone exige telefone único; nota de 1 a 5.
do $$ begin
  if not exists (select 1 from pg_constraint where conname = 'clientes_telefone_key') then
    alter table clientes add constraint clientes_telefone_key unique (telefone);
  end if;
  if not exists (select 1 from pg_constraint where conname = 'avaliacoes_nota_chk') then
    alter table avaliacoes add constraint avaliacoes_nota_chk check (nota between 1 and 5);
  end if;
end $$;

-- 2b) Cada cliente avalia cada assistência uma vez (unique COMPOSTO). Se existirem uniques de coluna única
--     em avaliacoes, eles limitariam o cliente a UMA avaliação no sistema inteiro; removemos e criamos o composto.
do $$ declare r record; begin
  for r in select conname from pg_constraint
           where conrelid = 'avaliacoes'::regclass and contype = 'u' and array_length(conkey, 1) = 1 loop
    execute format('alter table avaliacoes drop constraint %I', r.conname);
  end loop;
  if not exists (select 1 from pg_constraint
                 where conrelid = 'avaliacoes'::regclass and contype = 'u' and array_length(conkey, 1) = 2) then
    alter table avaliacoes add constraint avaliacoes_cliente_assistencia_key unique (cliente_id, assistencia_id);
  end if;
end $$;

-- 3) Categorias iniciais
insert into categorias_aparelhos (nome) values
  ('Celular'), ('Notebook'), ('PC'), ('Tablet'), ('Consoles'), ('Impressora'), ('TV'), ('Smartwatch')
on conflict (nome) do nothing;

-- 4) Índices
create index if not exists idx_avaliacoes_assistencia on avaliacoes (assistencia_id);
create index if not exists idx_fotos_assistencia on assistencia_fotos (assistencia_id);
