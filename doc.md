## BANCO DE DADOS
- prime.db
[tabelas]
    * geral (contém leads de todos os horários)
    * intervalo
    * fora_horario
    * listas

## CRONS
- cron1 (09h30)
    * Deleta todas as listas da campanha 'PRIME'
    * Cria listas: 'Geral' e 'Agora', pega o id de cada uma e adiciona/atualiza na tabela 'prime.listas.db'
    * Pega leads da tabela 'prime.geral.db' e adiciona na lista nova 'Geral' do 3cplus.
    * Pega leads da tabela 'prime.fora_horario.db' e adiciona na lista nova 'Agora' do 3cplus.

- cron2 (13h00)
    * Deleta todas as listas da campanha 'PRIME'
    * Cria listas: 'Geral' e 'Agora', pega o id de cada uma e adiciona/atualiza na tabela 'prime.listas.db'
    * Pega leads da tabela 'prime.fora_horario.db' e adicionar/atualizar em 'prime.geral.db'.
    * Pega leads da tabela 'prime.geral.db' e adiciona na lista nova 'Geral' do 3cplus.
    * Pega leads da tabela 'prime.intervalo.db' e adiciona na lista nova 'Agora' do 3cplus.

- cron3 (16h00)
    * Deleta todas as listas da campanha 'PRIME'
    * Cria listas: 'Geral' e 'Agora', pega o id de cada uma e adiciona/atualiza na tabela 'prime.listas.db'
    * Pega leads da tabela 'prime.intervalo.db' e adicionar/atualizar em 'prime.geral.db'.
    * Pega leads da tabela 'prime.geral.db' e adiciona na lista nova 'Geral' do 3cplus.

## REGRAS DE REGISTRO LEADS VINDO RD STATION
- 09h30 às 12h00: lead entra na tabela 'prime.geral.db' e na lista 'Agora' do 3cplus.
- 12h00 às 13h00: lead entra na tabela 'prime.intervalo.db'.
- 13h00 às 16h00: lead entra na tabela 'prime.geral.db' e na lista 'Agora' do 3cplus.
- 16h00 às 19h00: lead entra na tabela 'prime.geral.db' e na lista 'Agora' do 3cplus.
- 19h00 às 09h30: lead entra na tabela 'prime.fora_horario.db'.