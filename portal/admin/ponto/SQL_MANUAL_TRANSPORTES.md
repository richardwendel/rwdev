# SQL manual — transportes conhecidos

Execute primeiro `sql/soni_ponto_migration_20260727_transportes_reembolsos.sql`.
Depois execute o bloco abaixo na aba SQL. Ele não configura a LJ02 e não altera pontos de julho.

```sql
INSERT INTO trajetos_trabalho
  (loja_id,nome_trajeto,tipo_transporte,valor_ida,valor_volta,valor_total,tempo_medio,padrao_loja,observacoes,ativo)
SELECT l.id,'Transporte padrão vigente 2026-07','Transporte público',v.ida,v.volta,v.total,NULL,1,
       'Composição configurável cadastrada na terceira etapa do SONI PONTO.',1
FROM lojas_trabalho l
JOIN (
 SELECT 'LJ01' codigo,11.70 ida,11.70 volta,23.40 total UNION ALL
 SELECT 'LJ03',12.60,12.60,25.20 UNION ALL SELECT 'LJ04',12.60,12.60,25.20 UNION ALL
 SELECT 'LJ05',12.60,12.60,25.20 UNION ALL SELECT 'LJ06',17.00,17.00,34.00 UNION ALL
 SELECT 'LJ07',11.70,11.70,23.40
) v ON v.codigo=l.codigo_loja
WHERE NOT EXISTS (
 SELECT 1 FROM trajetos_trabalho t
 WHERE t.loja_id=l.id AND t.nome_trajeto='Transporte padrão vigente 2026-07'
);

UPDATE trajetos_trabalho t
JOIN lojas_trabalho l ON l.id=t.loja_id
SET t.padrao_loja=1
WHERE t.nome_trajeto='Transporte padrão vigente 2026-07'
  AND l.codigo_loja IN ('LJ01','LJ03','LJ04','LJ05','LJ06','LJ07');

INSERT INTO trajeto_trechos_trabalho
 (trajeto_id,direcao,ordem_trecho,tipo_transporte,descricao,tarifa_unitaria,quantidade,subtotal,vigencia_inicio,ativo)
SELECT t.id,d.direcao,d.ordem,'Transporte público',d.descricao,d.tarifa,1,d.tarifa,'2026-07-01',1
FROM trajetos_trabalho t JOIN lojas_trabalho l ON l.id=t.loja_id
JOIN (
 SELECT 'LJ01' codigo,'ida' direcao,1 ordem,'Condução 1' descricao,6.30 tarifa UNION ALL
 SELECT 'LJ01','ida',2,'Condução 2',5.40 UNION ALL SELECT 'LJ01','volta',1,'Condução 1',6.30 UNION ALL SELECT 'LJ01','volta',2,'Condução 2',5.40 UNION ALL
 SELECT 'LJ03','ida',1,'Condução 1',6.30 UNION ALL SELECT 'LJ03','ida',2,'Condução 2',6.30 UNION ALL SELECT 'LJ03','volta',1,'Condução 1',6.30 UNION ALL SELECT 'LJ03','volta',2,'Condução 2',6.30 UNION ALL
 SELECT 'LJ04','ida',1,'Condução 1',6.30 UNION ALL SELECT 'LJ04','ida',2,'Condução 2',6.30 UNION ALL SELECT 'LJ04','volta',1,'Condução 1',6.30 UNION ALL SELECT 'LJ04','volta',2,'Condução 2',6.30 UNION ALL
 SELECT 'LJ05','ida',1,'Condução 1',6.30 UNION ALL SELECT 'LJ05','ida',2,'Condução 2',6.30 UNION ALL SELECT 'LJ05','volta',1,'Condução 1',6.30 UNION ALL SELECT 'LJ05','volta',2,'Condução 2',6.30 UNION ALL
 SELECT 'LJ06','ida',1,'Condução 1',6.30 UNION ALL SELECT 'LJ06','ida',2,'Condução 2',5.40 UNION ALL SELECT 'LJ06','ida',3,'Condução 3',5.30 UNION ALL SELECT 'LJ06','volta',1,'Condução 1',6.30 UNION ALL SELECT 'LJ06','volta',2,'Condução 2',5.40 UNION ALL SELECT 'LJ06','volta',3,'Condução 3',5.30 UNION ALL
 SELECT 'LJ07','ida',1,'Condução 1',6.30 UNION ALL SELECT 'LJ07','ida',2,'Condução 2',5.40 UNION ALL SELECT 'LJ07','volta',1,'Condução 1',6.30 UNION ALL SELECT 'LJ07','volta',2,'Condução 2',5.40
) d ON d.codigo=l.codigo_loja
WHERE t.nome_trajeto='Transporte padrão vigente 2026-07'
AND NOT EXISTS (
 SELECT 1 FROM trajeto_trechos_trabalho x
 WHERE x.trajeto_id=t.id AND x.direcao=d.direcao AND x.ordem_trecho=d.ordem AND x.vigencia_inicio='2026-07-01'
);
```
