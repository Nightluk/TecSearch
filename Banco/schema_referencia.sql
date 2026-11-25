-- Reprodução do schema que você enviou. SÓ para testes locais; NÃO rode no Supabase (as tabelas já existem).
create table clientes (
  id integer generated always as identity primary key,
  nome varchar not null, email varchar not null unique, senha varchar not null,
  telefone varchar not null, created_at timestamptz not null default now());
create table assistencias (
  id integer generated always as identity primary key,
  cliente_id integer unique references clientes(id),
  nome_fantasia varchar not null, descricao text, telefone_whatsapp varchar not null,
  instagram varchar, facebook varchar, site varchar,
  cep varchar not null, logradouro varchar not null, numero varchar not null,
  bairro varchar not null, cidade varchar not null, estado char(2) not null,
  latitude numeric not null, longitude numeric not null,
  created_at timestamptz not null default now());
create table assistencia_fotos (
  id integer generated always as identity primary key,
  assistencia_id integer not null references assistencias(id), url_foto varchar not null);
create table categorias_aparelhos (
  id integer generated always as identity primary key, nome varchar not null unique);
create table assistencia_categorias (
  assistencia_id integer not null references assistencias(id),
  categoria_id integer not null references categorias_aparelhos(id),
  primary key (assistencia_id, categoria_id));
create table avaliacoes (
  id integer generated always as identity primary key,
  cliente_id integer not null references clientes(id),
  assistencia_id integer not null references assistencias(id),
  nota smallint not null, comentario varchar,
  created_at timestamptz not null default now(), updated_at timestamptz not null default now(),
  unique (cliente_id, assistencia_id));
