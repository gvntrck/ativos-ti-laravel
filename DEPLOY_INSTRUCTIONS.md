# Instruções de Deploy - Sistema de Ativos de TI (Laravel)

Este documento guia você pelo processo de implantação do sistema refatorado em seu servidor (Hostinger ou similar).

## 1. Preparação dos Arquivos

Todos os arquivos do novo sistema estão na pasta `src_laravel`.

1.  **Compactar:** Recomendo compactar todo o conteúdo da pasta `src_laravel` em um arquivo `.zip`.
2.  **Upload:** Faça o upload deste arquivo `.zip` para o diretório de destino no seu servidor (ex: `public_html/ativos-ti` ou apenas na raiz se for um subdomínio dedicado).
3.  **Extrair:** Extraia os arquivos no servidor.

## 2. Configuração do Ambiente

1.  **Renomear .env:** No servidor, renomeie o arquivo `.env.example` para `.env`.
2.  **Editar .env:** Abra o arquivo `.env` e configure os dados do banco de dados:
    ```ini
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nome_do_seu_banco
    DB_USERNAME=seu_usuario
    DB_PASSWORD=sua_senha
    ```
3.  **App Key:** Se possível, rode o comando abaixo no terminal do servidor (SSH) para gerar a chave de criptografia:
    ```bash
    php artisan key:generate
    ```
    *Se não tiver acesso ao terminal, você pode gerar uma chave localmente ou online (base64:...) e colar na variável `APP_KEY` do `.env`.*

## 3. Instalação de Dependências

Como você não conseguiu rodar o Composer localmente, precisará rodar no servidor. A Hostinger geralmente suporta Composer.

1.  Acesse o terminal (SSH) do seu servidor.
2.  Navegue até a pasta do projeto.
3.  Execute:
    ```bash
    composer install --optimize-autoloader --no-dev
    ```

## 4. Banco de Dados

O sistema utiliza tabelas novas. Você precisa rodar as migrações para criá-las.

1.  No terminal do servidor, execute:
    ```bash
    php artisan migrate
    ```
    *Isso criará as tabelas `computers`, `cellphones`, `users`, etc.*

## 5. Autenticação (Laravel Breeze)

O projeto está configurado para usar o Laravel Breeze. Se você ainda não instalou o Breeze, pode ser necessário rodar:
```bash
composer require laravel/breeze --dev
php artisan breeze:install
npm install
npm run build
```
*Nota: Se o servidor não tiver Node.js/NPM para compilar os assets (CSS/JS), você precisará compilar localmente (`npm run build`) e fazer upload da pasta `public/build` para o servidor.*

## 6. Permissões

Certifique-se de que as pastas `storage` e `bootstrap/cache` tenham permissão de escrita (775 ou 777 dependendo da configuração do servidor).

```bash
chmod -R 775 storage bootstrap/cache
```

## 7. Acesso

Aponte seu domínio ou subdomínio para a pasta `public` do projeto (ex: `public_html/ativos-ti/public`).

---

## Verificação

Após o deploy:
1.  Acesse a URL do sistema.
2.  Registre um usuário (se a opção de registro estiver ativa) ou insira um usuário manualmente no banco de dados para testar.
3.  Teste o cadastro de Computadores e Celulares.
4.  Teste a geração de relatórios (CSV).
