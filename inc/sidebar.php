<?php
function sidebar($paginaAtiva = '')
{
?>
    <style>
        .stock-layout {
            min-height: 100vh;
        }

        .stock-sidebar {
            width: 280px;
            height: 100vh;
            overflow-y: auto;
            background: #17212b;
        }

        .stock-sidebar hr {
            border-color: #394754;
        }

        .stock-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: #000000;
            border-radius: 6px;
            font-weight: 500;
            font-size: 1.05rem;
        }

        .stock-sidebar .nav-link:hover,
        .stock-sidebar .nav-link.active {
            color: #000000;
            background: #16a085;
        }

        .stock-sidebar .nav-link i {
            width: 2rem;
            color: #000000;
            font-size: 1.4rem;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .stock-layout {
                display: block !important;
            }

            .stock-sidebar {
                width: 280px;
            }
        }
    </style>
    <div class="d-flex align-items-start stock-layout">
        <div id="sidebarMenu" class="d-flex offcanvas-md offcanvas-start flex-column flex-shrink-0 position-sticky top-0 p-3 stock-sidebar">
            <a href="index.php" class="d-flex align-items-center mb-3 text-black text-decoration-none">
                <span class="fs-5">Controle de estoque</span>
            </a>
            <ul class="nav nav-pills flex-column mb-auto p-0">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= $paginaAtiva === 'produtos' ? 'active' : '' ?>" aria-current="page">
                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                        Produtos
                    </a>
                </li>
                <li>
                    <a href="venda.php" class="nav-link <?= $paginaAtiva === 'vendas' ? 'active' : '' ?>">
                        <i class="bi bi-cash-stack" aria-hidden="true"></i>
                        Vendas
                    </a>
                </li>
                <li>
                    <a href="compras.php" class="nav-link <?= $paginaAtiva === 'compras' ? 'active' : '' ?>">
                        <i class="bi bi-truck" aria-hidden="true"></i>
                        Compras
                    </a>
                </li>
                <li class="w-100">
                    <a href="relatorio.php" class="nav-link <?= $paginaAtiva === 'relatorios' ? 'active' : '' ?>">
                        <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                        Relatório
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-black text-decoration-none dropdown-toggle" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 me-2"></i>
                    <strong><?=$_SESSION['nome']?></strong>
                </a>
                <ul class="dropdown-menu text-small shadow w-100" aria-labelledby="dropdownUser2">
                    <li><a class="dropdown-item text-danger" href="auth/logout.php">Sair</a></li>
                    <?php if (($_SESSION['nivel'] ?? '') === 'admin') { ?>
                    <li><a class="dropdown-item text-black" href="admin.php">Painel Admin</a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    <?php }
