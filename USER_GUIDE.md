# CRM-AC User Guide

## Overview

CRM-AC is a modular ERP system. After logging in you land on the **Dashboard**, which shows only the modules your account has access to. There are two modules: **Inventory** and **Finance**.

---

## Navigation

The left sidebar contains all navigation links. On mobile, open it with the hamburger (☰) button in the top bar.

- **Home** — Dashboard with your active modules
- **Inventory** — Product and warehouse management
- **AI Statistics** — AI-powered sales analysis (Inventory module)
- **Finance** — Financial management

The top bar shows your role (Administrator / User) and a dark/light mode toggle button (moon/sun icon).

---

## Dashboard

Shows cards for each active module. Click a card to go directly to that module.

---

## Inventory Module

### Stats bar

Three counters at the top: total products, low-stock products, and number of categories.

### Tabs

| Tab | Purpose |
|---|---|
| Products | Full product catalog |
| Movements | Stock movement history |
| Warehouses | Manage physical warehouses |
| Transfers | Inter-warehouse stock transfers |
| Physical Inventory | Periodic physical counts |
| Alerts | Low-stock and reorder alerts |

---

### Products tab

**Search** — type a product name or SKU in the search bar. Results filter in real time.

**Filter by category** — click a category chip above the table to show only that category. Click again to clear.

**Add a product** — click **New Product** (green button). Fill in:
- Name (required)
- SKU (required, must be unique)
- Category (optional)
- Sale price (required)
- Cost (optional)
- Stock (current quantity)
- Minimum stock (triggers a low-stock alert when reached)

**Edit / Delete** — use the action buttons (pencil / trash) in the last column of each row.

**Import CSV** — click **Import CSV** to bulk-load products.
1. Click **Download template** to get the correct column layout.
2. Required columns: `sku`, `nombre`, `precio`.
3. Optional columns: `categoria`, `costo`, `stock`, `stock_minimo`, `descripcion`.
4. If a SKU already exists, the product is updated instead of duplicated.
5. Maximum 5,000 rows / 5 MB per file.

**Add a category** — click **New Category** (indigo button) to create a category before adding products.

---

### Movements tab

Shows every stock change for all products: date, product, SKU, movement type (entry/exit/adjustment), quantity, notes, and the user who made it. Read-only view.

---

### Warehouses tab

Lists all physical warehouse locations.

**Add warehouse** — click **New Warehouse**. Provide name and code. After creating a warehouse you can add internal locations (shelves, aisles, etc.) from its detail view.

**Delete a warehouse** — only warehouses with no stock can be deleted.

---

### Transfers tab

Move stock from one warehouse to another.

1. Click **New Transfer**.
2. Select origin warehouse, destination warehouse, product, and quantity.
3. The transfer starts in **Draft** status. Click **Send** to dispatch it.
4. At the destination warehouse, click **Receive** to confirm arrival and update stock.
5. Transfers in Draft can be deleted. Sent/Received transfers cannot.

---

### Physical Inventory tab

Reconcile actual stock against system records.

1. Click **New Physical Inventory** and select a warehouse.
2. A count is created with one row per product. The **Expected** column shows the current system quantity.
3. Enter the actual counted quantity in the **Counted** column for each product.
4. When all items are counted, click **Apply**. The system adjusts stock to match the counted values and records adjustment movements.
5. A physical inventory cannot be deleted once applied.

---

### Alerts tab

Shows products below their minimum stock level or reorder point. Click **Resolve** on an alert after you have restocked.

---

## AI Statistics (Inventory)

Accessible from **AI Statistics** in the sidebar.

Select a time period (7 days to 1 year) and click **Generate AI Report**. The page shows:

- **KPIs** — total sales count, total revenue, average ticket, units sold
- **Top 10 products** — bar chart by units sold in the selected period
- **Daily revenue** — line chart of revenue per day
- **Stale products** — products with no sales in the last 30 days
- **AI report** — a natural-language analysis generated locally by the AI model (no data is sent externally)

---

## Finance Module

The Finance page is divided into three areas:
1. **KPI bar** — period summary (top)
2. **AI Advisor** — four AI analysis types (middle)
3. **Tabs** — detailed data tables (bottom)

### Period selector

Use the **Period** dropdown (top-left) to change the reporting window: 7 days, 30 days, 90 days, 6 months, or 1 year. KPIs and charts update automatically.

### KPI bar

| KPI | Meaning |
|---|---|
| Revenue | Total income in the period |
| Expenses | Total outflows in the period |
| Profit | Revenue minus expenses, with margin % |
| Total balance | Sum of all account balances |

Two alert cards below show outstanding **Accounts Receivable** and **Accounts Payable** totals.

### Charts

- **Daily flow** — bar chart comparing income vs. expenses per day
- **Expense distribution** — donut chart of expenses by category

---

### AI Financial Advisor

Four analysis buttons, all powered by the local AI model (no cloud):

| Button | What it generates |
|---|---|
| Executive report | Summary of the period's financial health |
| Cash flow projection | Short-term cash forecast |
| Detect anomalies | Flags unusual transactions or patterns |
| Purchasing advisor | Recommendations on what and when to buy |

Click any button to generate the analysis. Click **Regenerate** to refresh with the same type.

---

### Transactions tab

Lists all income and expense transactions.

**New transaction** — click the amber **New transaction** button at the top.
- Type: Income or Expense
- Category
- Account (where the money goes/comes from)
- Amount, currency, date
- Description (optional)

**Delete** a transaction using the trash icon in its row (irreversible).

**Inter-account transfer** — click **Transfer** (indigo button at top). Select source account, destination account, amount, and date. Both accounts update instantly.

---

### Accounts tab

Shows all financial accounts (bank, cash, credit card, etc.) as cards with current balance.

**New account** — fill in name, account type, currency, and opening balance.

**Edit / Delete** — use the icons on each card. An account with movements cannot be deleted.

---

### Purchases tab

Purchase orders from suppliers.

**New purchase** — click **New purchase**:
1. Select supplier (must exist in the Suppliers tab first).
2. Add line items: product, quantity, unit cost, tax.
3. Set payment method, reference, and due date.
4. Save. The order starts as **Pending**.

**Receive a purchase** — click the receive icon on a pending order. Stock is added to inventory automatically. The order status changes to **Received**.

**Delete** — only Pending orders can be deleted.

---

### Suppliers tab

Catalog of vendors.

**New supplier** — provide name, RFC (tax ID), contact name, phone, email, and credit days.

**Edit / Delete** — use the action buttons. Suppliers with existing purchases cannot be deleted.

---

### Accounts Receivable (Por cobrar) tab

Tracks money customers owe you.

**New receivable** — provide customer name, amount, issue date, and due date.

**Record payment** — click the payment icon on a receivable, enter amount paid and payment date. A receivable can have partial payments; it closes automatically when fully paid.

**Status values:** Pending → Partial → Paid / Overdue.

---

### Accounts Payable (Por pagar) tab

Tracks money you owe suppliers. Payables are created automatically when a purchase order is marked as received.

**Record payment** — click the payment icon, enter amount and date. Partial payments are allowed.

---

### Budgets tab

Set spending targets by category and period.

**New budget** — select category, period (month/quarter/year), start date, and target amount. The system shows actual vs. budgeted spending.

---

### Returns tab

Two sub-sections side by side:

**Purchase returns** — money or credit back from a supplier.
- Click **New return**, select the original purchase, enter returned items and reason.

**Sale returns** — refunds to customers.
- Click **New return**, reference the original sale, enter returned items.

---

### Bank Reconciliation tab

Match your bank statement against system transactions.

1. Click **New reconciliation**.
2. Select account and the statement period. Enter the closing bank balance.
3. The system lists all transactions in that period. Check off each one that appears on your bank statement.
4. When the **Difference** column shows $0.00, click **Close** to finalize.

---

### Reports tab

**P&G (Profit & Loss)** — select a date range and click **See P&G**. Displays total revenue, total expenses, profit, and margin.

**Cash Flow** — click **Cash Flow** for the same date range. Shows daily inflows and outflows.

**Aging CxC / CxP** — click **Update** under each aging table to see which receivables/payables are current, 1–30 days overdue, 31–60 days, 61–90 days, and 90+ days.

---

### Fixed Assets tab

Track depreciable assets (equipment, vehicles, machinery).

**New asset** — provide:
- Name and category
- Original cost and purchase date
- Depreciation method (straight-line, declining balance, or units of production)
- Useful life in years

The system calculates depreciation automatically via a scheduled job. The table shows original cost, current book value, method, and status (active / disposed).

---

## User Account

Your name and email appear at the bottom of the sidebar.

**Log out** — click **Sign out** at the bottom of the sidebar.

**Dark / Light mode** — click the moon/sun icon in the top-right corner of any page. The preference is saved in your browser.

---

## Module Access

Each user only sees the modules their administrator has enabled. If a module is missing from your sidebar, contact your system administrator to request access.
