# GuardianKey para Magento 2

Essa é uma extensão para Magento 2 para integrá-lo ao GuardianKey.

GuardianKey é uma solução para proteger sistemas contra ataques de autenticação. Mais informação em https://guardiankey.io/

# Instalação da extensão

Em resumo, você deve:

1. Fazer o backup do banco e arquivos do Magento, e colocá-lo em modo de manutenção.
2. Copiar os arquivos da extensão para dentro do diretório do Magento.
3. Executar alguns comandos exigidos pelo Magento para habilitar a extensão.
4. Configurar a extensão no painel de administração do Magento.
5. Testar!


```bash
# Baixe a extensão para o seu servidor
wget https://github.com/guardiankey/guardiankey-magento2-authsecurity/archive/master.zip

# descompacte
unzip master.zip

# Crie diretório para a extensão na pasta do Magento
export MAGENTOROOT = "/var/www/html/magento" # CONFIGURE SUA PASTA AQUI
mkdir -p $MAGENTOROOT/app/code/GuardianKey/AuthSecurity/

# Mova os arquivos 
mv guardiankey-magento2-authsecurity/*  $MAGENTOROOT/app/code/GuardianKey/AuthSecurity/

# Agora, os comandos do Magento
cd $MAGENTOROOT
bin/magento maintenance:enable                        # maintenance mode
bin/magento module:status GuardianKey_AuthSecurity    # check the extension's status
bin/magento module:enable GuardianKey_AuthSecurity    # enable the extension
bin/magento setup:upgrade                             # read extension's information
bin/magento setup:di:compile                          # generate some files
bin/magento module:status GuardianKey_AuthSecurity    # confirm the extension's status
bin/magento cache:clean                               # clean cache
bin/magento maintenance:disable                       # disable the maintenance mode
```

A configuração da extensão pode ser feita no caminho abaixo, no painel de administração do Magento. Abaixo de cada campo há uma dica. Altere os valores e salve!
Você vai necessitar de algumas informações que podem ser encontradas em https://panel.guardiankey.io. 

```
Stores -> Configuration -> GuardianKey -> Auth Security
```

No painel de administração do GuardianKey, no menu Settings -> Auth Groups, você deve editar o seu authgroup e disabilitar o envio de emails. A extensão do Magento já envia mensagens.

# Customização

Você pode customizar as mensagens editando os arquivos no caminho abaixo.

```
app/code/GuardianKey/AuthSecurity/Controller/Index/templates/
```

# Mais ajuda

Instalação de extensões no Magento: https://devdocs.magento.com/extensions/install/
Painel de administração do GuardianKey: https://guardiankey.io/pt-br/documentation/panel-documentation/

Você também pode nos contactar por email: contact@guardiankey.io
