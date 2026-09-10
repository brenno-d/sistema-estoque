<?php
function sidebar($paginaAtiva = ''){
    ?>
    <div class="d-flex">
           <button
            class="btn btn-dark d-md-none position-fixed m-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#sidebar">
            ☰
        </button>
        <div class="d-flex offcanvas-md offcanvas-start flex-column flex-shrink-0 p-3 bg-light min-vh-100" style="width: 280px;">
            <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
                <svg class="bi me-2" width="40" height="32">
                    <use xlink:href="#bootstrap"></use>
                </svg>
                <span class="fs-4">Controle de estoque</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= $paginaAtiva === 'produtos' ? 'active' : 'link-dark' ?>" aria-current="page">
                        <img src="./assets/images/Box-<?= $paginaAtiva === 'produtos' ? 'white' : 'black' ?>.png" alt="Vendas" width="32" height="32" class="me-2">
                        Produtos
                    </a>
                </li>
                <li>
                    <a href="venda.php" class="nav-link <?= $paginaAtiva === 'vendas' ? 'active' : 'link-dark' ?>">
                        <img src="./assets/images/cashier-<?= $paginaAtiva === 'vendas' ? 'white' : 'black' ?>.png" alt="Vendas" width="32" height="32" class="me-2">
                        Vendas
                    </a>
                </li>
                <li>
                    <a href="compras.php" class="nav-link <?= $paginaAtiva === 'compras' ? 'active' : 'link-dark' ?>">
                        <img src="./assets/images/Truck-<?= $paginaAtiva === 'compras' ? 'white' : 'black' ?>.png" alt="Vendas" width="32" height="32" class="me-2">
                        Compras
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-link <?= $paginaAtiva === 'relatorios' ? 'active' : 'link-dark' ?>">
                        <img src="./assets/images/chart-<?= $paginaAtiva === 'relatorios' ? 'white' : 'black' ?>.png" alt="Vendas" width="32" height="32" class="me-2">
                        Relatórios
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://cdn.pixabay.com/photo/2023/02/18/11/00/icon-7797704_1280.png" alt="" width="32" height="32" class="rounded-circle me-2">
                    <strong>Nome do funcionário</strong>
                </a>
                <ul class="dropdown-menu text-small shadow w-100" aria-labelledby="dropdownUser">
                    </li>
                    <li><a class="dropdown-item text-danger" href="/sistema-estoque/auth/logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
<?php }
