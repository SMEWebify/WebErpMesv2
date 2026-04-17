#!/bin/bash
# WebErpMesV2 一键安装脚本 - 适用于 CentOS 8 / AlmaLinux 8 / Rocky Linux 8
# 必须使用 root 权限在项目根目录运行此脚本

set -e

echo "================================================="
echo "  WebErpMesV2 一键安装脚本 (CentOS 8 / PHP 8.2) "
echo "  非 Docker 裸机环境部署 "
echo "================================================="

if [ "$EUID" -ne 0 ]; then
  echo "错误: 请使用 root 权限运行此脚本 (例如: sudo ./install-centos8.sh)"
  exit 1
fi

if [ ! -f "artisan" ]; then
  echo "错误: 请在项目根目录 (包含 artisan 文件的目录) 下运行此脚本"
  exit 1
fi

PROJECT_DIR=$(pwd)

echo "[1/10] 安装并更新基础仓库 (EPEL & Remi)..."
dnf install -y epel-release dnf-utils curl wget unzip git
dnf install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

echo "[2/10] 安装 PHP 8.2 及必需扩展..."
dnf module reset php -y
dnf module enable php:remi-8.2 -y
dnf install -y php php-cli php-fpm php-mysqlnd php-sqlite3 php-xml php-mbstring php-zip php-curl php-gd php-bcmath php-intl php-ldap

echo "[3/10] 安装 Nginx & SQLite..."
dnf install -y nginx sqlite

echo "[4/10] 安装 Node.js 20.x..."
curl -fsSL https://rpm.nodesource.com/setup_20.x | bash -
dnf install -y nodejs

echo "[5/10] 安装 Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "[6/10] 配置项目环境变量 & 依赖..."
# 如果没有 .env 文件则复制一份
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# 配置 SQLite (无缝本地数据库) 并移除可能产生依赖问题的 Redis
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
# 注释掉 MySQL 相关的配置
sed -i 's/^DB_DATABASE=/#DB_DATABASE=/' .env || true
sed -i 's/^DB_HOST=/#DB_HOST=/' .env || true
sed -i 's/^DB_PORT=/#DB_PORT=/' .env || true

# 配置缓存、队列、Session为基础文件模式
sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' .env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' .env
sed -i 's/^BROADCAST_DRIVER=.*/BROADCAST_DRIVER=log/' .env

# 确保 SQLite 文件存在
mkdir -p database
touch database/database.sqlite

# 允许 Composer 以 root 身份运行
export COMPOSER_ALLOW_SUPERUSER=1

echo "  -> 正在安装 PHP 依赖..."
composer install --no-dev --optimize-autoloader

echo "  -> 生成应用密钥并执行数据迁移与填充..."
php artisan key:generate --force
php artisan migrate:fresh --seed --force

echo "  -> 正在安装前端依赖并编译打包 (Vite)..."
npm install
npm run build

echo "[7/10] 配置文件权限与 SELinux..."
# 给 Nginx 设置所有权
chown -R nginx:nginx "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
chmod -R 775 "$PROJECT_DIR/database"

# 临时并永久放行 SELinux (推荐对新手更友好的 Permissive 模式，以防止权限问题)
if command -v setenforce &> /dev/null; then
    setenforce 0 || true
    sed -i 's/^SELINUX=enforcing/SELINUX=permissive/' /etc/selinux/config || true
fi

echo "[8/10] 配置 Nginx 与 PHP-FPM..."
NGINX_CONF="/etc/nginx/conf.d/weberpmes.conf"
cat > $NGINX_CONF <<EOF
server {
    listen 80;
    server_name _;
    root $PROJECT_DIR/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# 移除 Nginx 默认站点配置避免冲突
rm -f /etc/nginx/conf.d/default.conf

# 修改 PHP-FPM 运行用户为 nginx
sed -i 's/^user = .*/user = nginx/g' /etc/php-fpm.d/www.conf
sed -i 's/^group = .*/group = nginx/g' /etc/php-fpm.d/www.conf
sed -i 's/^;listen.owner = nobody/listen.owner = nginx/g' /etc/php-fpm.d/www.conf
sed -i 's/^;listen.group = nobody/listen.group = nginx/g' /etc/php-fpm.d/www.conf

echo "[9/10] 启动服务并设置开机自启..."
systemctl enable --now php-fpm
systemctl enable --now nginx
systemctl restart php-fpm
systemctl restart nginx

echo "[10/10] 配置防火墙 (放行 80 端口)..."
if command -v firewall-cmd &> /dev/null; then
    firewall-cmd --permanent --add-service=http || true
    firewall-cmd --reload || true
fi

echo "================================================="
echo "  ✅ 安装完成！"
echo "  请在浏览器中访问您的服务器 IP 地址即可访问项目。"
echo "  "
echo "  项目部署路径: $PROJECT_DIR"
echo "  前端已经通过 Vite 打包至 public/build 目录下"
echo "  后端默认连接已配置为 SQLite，测试数据已填充完毕"
echo "================================================="
