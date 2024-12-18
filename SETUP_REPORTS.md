# Configuração do Sistema de Relatórios

Siga estes passos na ordem para configurar o sistema de relatórios:

## 1. Instalar o Composer
1. Execute o arquivo `install_composer.bat`
2. Siga as instruções do instalador
3. Após a instalação, feche e reabra o terminal se estiver usando um

## 2. Criar as Pastas Necessárias
1. Execute o arquivo `create_folders.bat`
2. Isso criará as pastas assets e assets/img

## 3. Criar o Logo
1. Acesse no navegador: `http://localhost/Myproject/assets/img/logo.php`
2. Isso gerará automaticamente um logo padrão

## 4. Instalar Dependências
1. Abra um terminal na pasta do projeto (c:/xampp/htdocs/Myproject)
2. Execute o comando: `composer install`
3. Aguarde a instalação das dependências

## 5. Testar o Sistema
1. Acesse o sistema normalmente
2. Faça login como supervisor ou administrador
3. Clique no menu "Relatórios"
4. Selecione um período e gere um relatório PDF

## Possíveis Problemas e Soluções

### Se o Composer não instalar:
1. Baixe manualmente em https://getcomposer.org/download/
2. Execute o instalador
3. Reinicie o computador

### Se o logo não aparecer:
1. Verifique se a pasta assets/img existe
2. Verifique se o arquivo logo.png foi criado
3. Se necessário, use qualquer outra imagem PNG e renomeie para logo.png

### Se o PDF não gerar:
1. Verifique se o TCPDF foi instalado corretamente
2. Verifique as permissões da pasta
3. Verifique os logs do PHP para erros

## Estrutura Final Esperada
```
Myproject/
├── assets/
│   └── img/
│       ├── logo.php
│       └── logo.png
├── vendor/
│   └── tecnickcom/
│       └── tcpdf/
├── composer.json
├── composer.lock
└── ... (outros arquivos)
```

Para qualquer problema, verifique:
1. Se o PHP está instalado e configurado corretamente
2. Se o Composer está instalado e no PATH do sistema
3. Se todas as dependências foram instaladas
4. Se as permissões das pastas estão corretas
