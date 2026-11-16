<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MyOwnBills - Criar Conta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="d-flex align-items-center py-4 bg-body-tertiary vh-100">

    <main class="form-signin w-100 m-auto" style="max-width: 380px;">
        <form>
            <div class="d-flex align-items-center mb-2">
                <img class="me-3" src="Assets/icons/tecsearch-logo.svg" alt="Logo" width="300" height="70">
            </div>

            <h2 class="h6 mb-3 text-body-secondary fw-normal">Preencha os dados para criar a sua conta:</h2>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="floatingName" placeholder="Seu Nome">
                <label for="floatingName">Nome Completo</label>
            </div>
            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com">
                <label for="floatingInput">Endereço de Email</label>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="floatingPassword" placeholder="Password">
                <label for="floatingPassword">Senha</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="floatingConfirmPassword" placeholder="Confirm Password">
                <label for="floatingConfirmPassword">Confirmar Senha</label>
            </div>

            <div class="form-check text-start mb-3">
                <input class="form-check-input" type="checkbox" value="remember-me" id="checkDefault">
                <label class="form-check-label" for="checkDefault">
                    Aceita os <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ">Termos e Condições</a>?
                </label>
            </div>

            <button class="btn w-100 py-2 mb-3 text-white" style="background-color: #087cf0;" type="submit">Cadastrar</button>


            <div class="text-center">
                <a href="entrar.php" class="text-decoration-none text-body-secondary small">Já tem uma conta? Faça login</a>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>