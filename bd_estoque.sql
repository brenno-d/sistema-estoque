CREATE DATABASE estoque;
USE estoque;
CREATE TABLE tb_produtos(
cd_produto INT PRIMARY KEY AUTO_INCREMENT,
nm_produto VARCHAR(90) NOT NULL,
qt_estoque INT NOT NULL,
vl_preco DECIMAL(8,2) NOT NULL
);
CREATE TABLE tb_categorias(
cd_categoria INT PRIMARY KEY AUTO_INCREMENT,
nm_categoria VARCHAR(50) NOT NULL
);
CREATE TABLE tb_categorias_produtos(
cd_categoria_produto INT PRIMARY KEY AUTO_INCREMENT,
id_categoria INT NOT NULL,
id_produto INT NOT NULL,
FOREIGN KEY (id_categoria) REFERENCES tb_categorias(cd_categoria) 
);
CREATE TABLE tb_funcionarios(
cd_funcionario INT PRIMARY KEY AUTO_INCREMENT,
nm_funcionario VARCHAR(100),
ds_email_funcionario VARCHAR(100),
ds_tel_funcionario VARCHAR(15),
ds_nivel_funcionario ENUM('admin', 'usuario') NOT NULL
);
CREATE TABLE tb_vendas(
cd_venda INT PRIMARY KEY AUTO_INCREMENT,
dt_venda DATETIME DEFAULT current_timestamp,
vl_total DECIMAL NOT NULL,
id_funcionario INT NULL,
FOREIGN KEY (id_funcionario) REFERENCES tb_funcionarios(cd_funcionario)
);
CREATE TABLE tb_produtos_vendas(
cd_produto_venda INT PRIMARY KEY AUTO_INCREMENT,
id_produto INT NOT NULL,
id_venda INT NOT NULL,
qt_produto INT NOT NULL,
FOREIGN KEY (id_produto) REFERENCES tb_produtos(cd_produto),
FOREIGN KEY (id_venda) REFERENCES tb_vendas(cd_venda)
);
CREATE TABLE tb_entradas(
cd_entrada INT PRIMARY KEY AUTO_INCREMENT,
id_usuario INT NOT NULL,
dt_entrada DATETIME DEFAULT current_timestamp,
FOREIGN KEY (id_usuario) REFERENCES tb_funcionarios(cd_funcionario)
);
CREATE TABLE tb_compras(
cd_compra INT PRIMARY KEY AUTO_INCREMENT,
dt_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
vl_total_compra DECIMAL(8,2) NOT NULL
);
CREATE TABLE tb_produtos_compras(
cd_produto_compra INT PRIMARY KEY AUTO_INCREMENT,
id_produto INT NOT NULL,
id_compra INT NOT NULL,
qt_produto INT NOT NULL,
FOREIGN KEY (id_compra) REFERENCES tb_compras(cd_compra),
FOREIGN KEY (id_produto) REFERENCES tb_produtos(cd_produto)
);
CREATE USER funcionario
IDENTIFIED BY 'func123';
