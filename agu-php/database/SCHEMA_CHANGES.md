# Ajustes do esquema

- Removido `chk_tasks_not_own_parent`: MySQL rejeita CHECK que referencia AUTO_INCREMENT (erro 3818). Triggers BEFORE INSERT/UPDATE rejeitam pai igual ao proprio id; no INSERT o id automatico ainda vale 0, entao o pai precisa existir previamente. O FK composto continua exigindo o mesmo criador.
- Removidos `idx_tags_user` (coberto por `uq_tags_user_name`) e a declaracao explicita `idx_tasks_parent_owner`. O InnoDB cria automaticamente o indice necessario para `fk_tasks_parent_owner`; ele continua existindo fisicamente. Os demais indices de FKs foram preservados.
- Adicionado `idx_tasks_creator_created (created_by, deleted_at, created_at)` para listagem por usuario, exclusao logica e data. FULLTEXT foi avaliado e adiado: nao atende diretamente busca parcial por substring, possui stopwords/tamanho minimo e exige definir a semantica de busca na API.
- Adicionado `users.roles JSON NOT NULL DEFAULT ('["ROLE_USER"]')`, mapeavel como JSON pelo Doctrine. A API deve validar um array de roles e aplicar as regras de autorizacao do Symfony.
- Os dois triggers preenchem `completed_at` em `done` com UTC quando ausente, preservam uma data fornecida e limpam a data em qualquer outro status. `canceled` e terminal, mas nao representa conclusao. A API deve atualizar/recarregar os campos alterados pelo banco no estado da entidade Doctrine.
- BEFORE UPDATE valida trocas contra `task_status_transitions` com SQLSTATE `45000`; atualizar sem mudar status e permitido. INSERT permite qualquer status inicial valido pelo FK. Seeds mantidos integralmente; catalogos devem ser alterados somente por migrations controladas.
- Profundidade/ciclos indiretos foram avaliados e ficaram na aplicacao: limitar a um nivel mudaria uma regra funcional nao definida; percorrer toda a arvore em trigger e controlar concorrencia ampliaria a complexidade. Antes de validar ancestrais e alterar a hierarquia, a API deve serializar escritas por criador na mesma transacao (por exemplo, `SELECT id FROM users WHERE id = ? FOR UPDATE`). Triggers garantem a autorreferencia direta mesmo fora da API.
- Permanecem na aplicacao: JWT/login por username, compartilhamento e acesso por usuario, auditoria na mesma transacao, soft delete/retencao, storage privado e validacao de arquivos, UTC nas conexoes e mapeamento Doctrine de UNSIGNED/CHECKs/triggers em migrations. Nenhum dado de usuario ou senha foi incluido no esquema.

## Validacao

Executada em 2026-11-11, imagem `mysql:8.0`, servidor **8.0.44**, container `mysql_validate_codex`, porta `127.0.0.1:3399`. Execucao pelo entrypoint confirmada nos logs; repeticao via stdin em outro banco vazio com **exit 0 e zero erros SQL**. Resultado: 9 tabelas, 2 triggers, 6 status e 15 transicoes. Apenas avisos padrao da imagem na inicializacao.

Comandos usados (fixtures de usuarios/tarefas criadas somente no container descartavel):

```sh
docker run --detach --name mysql_validate_codex --publish 127.0.0.1:3399:3306 \
  --env MYSQL_ALLOW_EMPTY_PASSWORD=yes --env MYSQL_DATABASE=todo_validate \
  --mount type=bind,src=/home/workspace/agu/projeto/agu-php/database/todo_list_schema.sql,dst=/docker-entrypoint-initdb.d/001-schema.sql,readonly mysql:8.0
docker exec mysql_validate_codex mysqladmin --protocol=tcp --host=127.0.0.1 ping --silent
# Saida: mysqld is alive
docker exec -i mysql_validate_codex mysql --batch --skip-column-names <<'SQL'
CREATE DATABASE todo_validate_replay;
SQL
docker exec -i mysql_validate_codex mysql --batch --skip-column-names todo_validate_replay < agu-php/database/todo_list_schema.sql
# Saida vazia, exit 0.
# Testes: mesmo docker exec -i ... mysql ... todo_validate_replay, SQL via stdin.
docker logs mysql_validate_codex
# running /docker-entrypoint-initdb.d/001-schema.sql
# MySQL init process done. Ready for start up.
docker rm --force --volumes mysql_validate_codex
# Saida: mysql_validate_codex; exit 0.
docker ps -a --filter 'name=^/mysql_validate_codex$' --format '{{.Names}}'
# Saida vazia: remocao confirmada.
```

| Teste | Saida resumida |
| --- | --- |
| INSERT usuario omitindo roles | `["ROLE_USER"]`, exit 0 |
| INSERT raiz e subtarefa de pai existente do mesmo criador | Aceitos, exit 0 |
| INSERT done sem data / com data fornecida | Preenche / preserva; verificacoes SQL = 1 |
| INSERT canceled e backlog com data fornecida | Limpa; verificacoes SQL = 1 |
| INSERT pai igual ao id explicito / ao proximo AUTO_INCREMENT / inexistente | ERROR 1644 (45000), exit 1 esperado em cada caso |
| INSERT pai de outro criador | FK: ERROR 1452 (23000), exit 1 esperado |
| UPDATE pai igual ao proprio id | ERROR 1644 (45000), exit 1 esperado |
| UPDATE desanexar e reanexar filha | Aceitos, exit 0 |
| UPDATE in_progress -> done; done sem troca; done com data NULL | Preenche; preserva; preenche novamente; verificacoes = 1 |
| UPDATE done -> todo; in_progress -> canceled -> todo | Aceitos; completed_at limpo, verificacoes = 1 |
| UPDATE todo -> done | ERROR 1644 (45000), exit 1 esperado; status e data permanecem intactos |
| Todos os 36 pares de status | 15 transicoes e 6 updates sem troca aceitos; 15 transicoes proibidas com ERROR 1644 (45000) |
| INSERT titulo vazio / status inexistente | CHECK 3819 / FK 1452 preservados; exit 1 esperado |
| Indices em information_schema.statistics | `fk_tasks_parent_owner (parent_task_id,created_by)` automatico; `idx_tasks_creator_created (created_by,deleted_at,created_at)` presente; `idx_tags_user` ausente |

Exemplos de SQL executado (ids 10/11/12 sao fixtures; cada erro esperado executado separadamente):

```sql
INSERT INTO tasks (id, created_by, title) VALUES (10, 1, 'Raiz');
INSERT INTO tasks (id, created_by, parent_task_id, title) VALUES (11, 1, 10, 'Filha');
INSERT INTO tasks (id, created_by, status_id, title) VALUES (12, 1, 3, 'Em andamento');
UPDATE tasks SET status_id = 5 WHERE id = 12; -- aceita, preenche completed_at
UPDATE tasks SET status_id = 2 WHERE id = 12; -- aceita, limpa completed_at
UPDATE tasks SET status_id = 5 WHERE id = 12; -- ERROR 1644: transicao nao permitida
UPDATE tasks SET parent_task_id = id WHERE id = 10; -- ERROR 1644: proprio pai
INSERT INTO tasks (id, created_by, parent_task_id, title)
VALUES (90, 1, 90, 'Autorreferencia'); -- ERROR 1644: proprio pai
SELECT AUTO_INCREMENT INTO @next_id FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'tasks';
INSERT INTO tasks (created_by, parent_task_id, title)
VALUES (1, @next_id, 'Autorreferencia automatica'); -- ERROR 1644: pai deve existir
```

Seeds comparados ao arquivo base e preservados integralmente. A validacao cobre autorreferencia direta; ciclos indiretos, profundidade e concorrencia da hierarquia dependem da API.

Referencia: [MySQL 8.0: triggers e NEW.id antes do AUTO_INCREMENT](https://dev.mysql.com/doc/refman/8.0/en/trigger-syntax.html).
