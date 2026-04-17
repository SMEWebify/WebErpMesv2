# ΣEM (WebErpMesv2) - Code Wiki

## 1. 项目整体架构 (Architecture Overview)

ΣEM 是一个基于 **Laravel 12 (PHP 8.2)** 构建的集成 ERP (企业资源计划) 与 MES (制造执行系统) 的应用，专门针对钣金加工和工业机械制造行业设计。

项目采用经典的 **MVC (Model-View-Controller) + Service 层** 的架构模式，结合了现代 Web 技术栈：
*   **后端 (Backend)**: Laravel 框架，负责路由、控制器业务分发、中间件拦截、Eloquent ORM 数据持久化。
*   **前端 (Frontend)**: 采用混合渲染模式，包含传统的 Blade 模板（服务器端渲染）、Livewire（处理复杂的动态表格和表单）、以及基于 Vue.js / React 构建的富交互组件。样式主要使用 Bootstrap 4 / AdminLTE 以及 Tailwind CSS。
*   **数据库 (Database)**: 支持 MySQL / PostgreSQL，使用 Laravel 的 Migrations 管理数据库 Schema，Seeders 进行初始数据填充。
*   **异步与实时 (Async & Real-time)**: 
    *   利用 Laravel Queue / Jobs 处理耗时任务（如复杂计算、邮件发送、N2P推送等）。
    *   通过 Laravel Echo 和 Pusher 实现 WebSocket 实时通信（例如车间生产状态的实时追踪）。
*   **缓存 (Cache)**: 使用 Redis 管理会话、缓存以及队列。

---

## 2. 主要模块职责 (Main Modules)

项目按照业务领域驱动 (Domain-Driven) 将控制器、模型和服务进行了清晰的目录划分：

*   **Workflow (销售与工作流模块)**: `app/Http/Controllers/Workflow/`
    *   负责客户 CRM 核心链路，包含线索 (Leads)、商机 (Opportunities)、报价单 (Quotes)、销售订单 (Orders)、发货单 (Deliveries) 以及退货 (Returns) 的管理。
*   **Planning & Workshop (生产计划与车间执行 - MES)**: `app/Http/Controllers/Planning/` & `app/Http/Controllers/Workshop/`
    *   负责制造执行核心，包括生产排程、甘特图 (Gantt)、实时任务追踪、Andon 报警、资源分配以及车间工单下发。
*   **Products & Inventory (产品与库存模块)**: `app/Http/Controllers/Products/`
    *   管理物料、半成品、成品。负责库存批次 (Batches)、序列号 (Serial Numbers)、库位 (Locations) 和库存盘点 (Inventory)。
*   **Purchases (采购模块)**: `app/Http/Controllers/Purchases/`
    *   处理供应链业务，涵盖询价单 (RFQ)、采购订单 (Purchase Orders)、采购收货单 (Receipts) 以及采购发票。
*   **Quality & Inspection (质量与检验模块)**: `app/Http/Controllers/Quality/` & `app/Http/Controllers/Inspection/`
    *   实现 ISO 9001 等质量标准管理。涵盖不合格品 (Non-conformities)、质量控制设备校验、FMEA/AMDEC 分析、检验记录和流程图。
*   **Methods (工艺与方法模块)**: `app/Http/Controllers/Methods/`
    *   定义生产工艺路线 (Routings)、标准工时、工作中心 (Services)、设备与模具 (Tools) 以及物料清单 (BOM)。
*   **Companies (企业关系模块)**: `app/Http/Controllers/Companies/`
    *   统一管理客户 (Customers) 和供应商 (Suppliers) 的基础信息、联系人及地址。
*   **Accounting (财务会计模块)**: `app/Http/Controllers/Accounting/`
    *   处理销售发票、增值税 (VAT)、付款条件和财务对账。
*   **Admin & HR (后台管理与人力资源)**: `app/Http/Controllers/Admin/` & `app/Http/Controllers/HumanResources/`
    *   处理用户权限 (RBAC)、考勤 (Attendance)、全局设置和系统模板。

---

## 3. 关键类与函数说明 (Key Classes & Services)

为避免 Controller 臃肿，项目提取了大量的 `Service` 类，专门负责复杂的业务逻辑计算。

### 核心领域模型 (Models)
*   **`App\Models\Workflow\Order` & `OrderLine`**: 销售订单及其明细。是连接生产 (Tasks) 和财务 (Invoices) 的核心枢纽。
*   **`App\Models\Workflow\Quote` & `QuoteLine`**: 报价单，支持多版本及详细的工艺成本计算。
*   **`App\Models\Planning\Task`**: 生产任务（工单），对应一个具体的加工工序。包含开始、暂停、结束等实时车间状态。
*   **`App\Models\Products\Products`**: 物料主数据，区分原材料、组件和最终产品。

### 核心服务类 (Services)
代码位置：`app/Services/`
*   **`QuoteCalculatorService` / `OrderCalculatorService`**: 
    *   **职责**: 计算报价单/订单的成本与售价。
    *   **逻辑**: 递归遍历物料清单 (BOM) 和工艺路线 (Routings)，计算材料费、人工费（工时 × 费率）及委外加工费用。
*   **`TaskService`**: 
    *   **职责**: 管理车间生产任务的生命周期。
    *   **关键方法**: 处理任务的状态流转（Pending -> In Progress -> Completed），并记录工人的实际耗时 (`TaskActivities`)。
*   **`StockCalculationService`**: 
    *   **职责**: 处理库存相关的复杂逻辑。
    *   **逻辑**: 在采购入库、生产领料或成品发货时，自动生成库存移动记录 (`StockMoves`)，更新可用库存和物理库存。
*   **`InvoiceService`**: 
    *   **职责**: 处理开票逻辑。
    *   **逻辑**: 根据已发货的 `Delivery` 或者订单的完成度自动生成对应的发票记录，并计算相应的税额 (VAT)。

---

## 4. 项目依赖关系 (Dependencies)

项目通过 `composer.json` 和 `package.json` 管理依赖，以下是核心扩展包：

### 后端 (PHP/Laravel)
*   **`spatie/laravel-permission`**: 实现基于角色和权限的细粒度访问控制 (RBAC)。
*   **`spatie/laravel-activitylog`**: 记录用户操作日志，提供系统审计追踪。
*   **`barryvdh/laravel-dompdf` & `webklex/laravel-pdfmerger`**: 用于将 Blade 视图转换为 PDF，常用于生成正式的报价单、订单合同及质检报告。
*   **`maatwebsite/excel` & `phpoffice/phpspreadsheet`**: 用于处理数据导出与导入（例如导出生产报表或导入 BOM 清单）。
*   **`livewire/livewire`**: 构建动态且无需编写复杂 JS 的前端组件（例如实时库存查看、动态表单）。
*   **`directorytree/ldaprecord-laravel`**: 支持企业内部 Active Directory/LDAP 账号登录。
*   **`pusher/pusher-php-server`**: 结合 Laravel Echo，用于实现前后端的 WebSocket 实时消息推送。

### 前端 (Node/NPM)
*   基于 Laravel Mix 或 Vite 构建前端资源。
*   核心库包括：Vue/React（用于特定富交互模块）、Alpine.js（处理简单的页面交互）、Bootstrap 4 / AdminLTE（UI 框架和组件库）。

---

## 5. 项目运行方式 (How to Run)

项目支持多种环境部署，推荐使用 Docker 进行快速启动。

### 方式一：Docker 运行（推荐用于本地开发和快速体验）
前提：需安装 Docker 和 Docker Compose。

```bash
# 1. 克隆代码仓库
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2

# 2. 构建并启动 Docker 容器
docker compose up --build
```
> 启动完成后，可以通过浏览器访问 `http://localhost:45060`。

### 方式二：本地直接安装 (Local Installation)
前提：需安装 PHP 8.2+, Composer, Node.js 以及 MySQL/PostgreSQL 和 Redis。

```bash
# 1. 克隆并进入目录
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2

# 2. 配置环境变量
cp .env.example .env
# 请在 .env 中配置好 DB_*, REDIS_*, MAIL_* 等相关信息

# 3. 安装 PHP 和 Node 依赖
composer install
npm install

# 4. 生成应用密钥并初始化数据库
php artisan key:generate
php artisan migrate --seed

# 5. 编译前端资源并启动本地服务器
npm run dev
php artisan serve
```
> 启动完成后，可通过 `http://localhost:8000` 访问。

### 初始化配置注意事项
系统安装后，必须在后台进行以下两项基础配置，否则无法正常添加报价单或订单明细：
1. **默认增值税 (Default VAT)**: 访问 `Accounting -> VAT` 并设置一个默认项。
2. **默认单位 (Default Unit)**: 访问 `Methods -> Units` 并设置一个默认项。

### 自定义 Artisan 命令 (Custom Artisan Commands)
项目中内置了一些实用的命令行工具：
*   `php artisan wem:diagnostics`: 诊断当前本地运行环境是否满足项目需求。
*   `php artisan wem:n2p:push-order {orderId}`: 将指定的订单推送到 Nest2Prod (N2P) 系统。
*   `php artisan quality:dispatch-calibration-alerts`: 发送设备校验逾期或即将到期的提醒通知。
*   `php artisan ldap:import-users`: 从 LDAP 目录导入用户到本地数据库。
