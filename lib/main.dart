import 'package:flutter/material.dart';

void main() {
  runApp(const ModiriatHesabhaApp());
}

class ModiriatHesabhaApp extends StatelessWidget {
  const ModiriatHesabhaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'مدیریت حساب‌ها',
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF1565C0),
        ),
        scaffoldBackgroundColor: const Color(0xFFF5F7FA),
      ),
      home: const Directionality(
        textDirection: TextDirection.rtl,
        child: HomePage(),
      ),
    );
  }
}

class Customer {
  Customer({
    required this.name,
    this.phone = '',
    this.city = '',
    this.isPinned = false,
    this.balance = 0,
  });

  String name;
  String phone;
  String city;
  bool isPinned;
  double balance;
}

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  final List<Customer> customers = [];

  void _openCustomers() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => Directionality(
          textDirection: TextDirection.rtl,
          child: CustomersPage(
            customers: customers,
            onChanged: () => setState(() {}),
          ),
        ),
      ),
    );
  }

  void _addCustomer() {
    showDialog(
      context: context,
      builder: (_) => Directionality(
        textDirection: TextDirection.rtl,
        child: CustomerFormDialog(
          onSave: (customer) {
            setState(() {
              customers.add(customer);
            });
          },
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'مدیریت حساب‌ها',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            tooltip: 'تنظیمات',
            onPressed: () {},
            icon: const Icon(Icons.settings_outlined),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              _announcement(),

              const SizedBox(height: 16),

              Row(
                children: [
                  Expanded(
                    child: _dashboardCard(
                      title: 'مدیریت کل حساب‌ها',
                      subtitle: 'همه مشتریان و حساب‌ها',
                      icon: Icons.account_balance_wallet_outlined,
                      color: const Color(0xFF1565C0),
                      onTap: _openCustomers,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _dashboardCard(
                      title: 'برداشت‌های خودم',
                      subtitle: 'برداشت‌ها و تسویه‌ها',
                      icon: Icons.arrow_upward_rounded,
                      color: Colors.red,
                      onTap: () {},
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: _dashboardCard(
                      title: 'طلب‌ها',
                      subtitle: 'مبالغی که باید دریافت شود',
                      icon: Icons.trending_up_rounded,
                      color: Colors.green,
                      onTap: () {},
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _dashboardCard(
                      title: 'بدهی‌ها',
                      subtitle: 'مبالغی که باید پرداخت شود',
                      icon: Icons.trending_down_rounded,
                      color: Colors.red,
                      onTap: () {},
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 20),

              _sectionTitle(
                'خلاصه موجودی',
                Icons.account_balance_outlined,
              ),

              const SizedBox(height: 10),

              _currencyCard('افغانی', 'AFN', '0', '؋'),
              _currencyCard('تومان', 'TOMAN', '0', 'ت'),
              _currencyCard('دلار', 'USD', '0', '\$'),
              _currencyCard('لیر ترکیه', 'TRY', '0', '₺'),

              const SizedBox(height: 20),

              _sectionTitle(
                'وضعیت حساب‌ها',
                Icons.people_outline,
              ),

              const SizedBox(height: 10),

              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: _countItem(
                        'مشتریان',
                        customers.length.toString(),
                        Icons.people_alt_outlined,
                      ),
                    ),
                    Expanded(
                      child: _countItem(
                        'طلب',
                        '0',
                        Icons.arrow_downward_rounded,
                      ),
                    ),
                    Expanded(
                      child: _countItem(
                        'بدهی',
                        '0',
                        Icons.arrow_upward_rounded,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              SizedBox(
                width: double.infinity,
                height: 54,
                child: FilledButton.icon(
                  onPressed: _addCustomer,
                  icon: const Icon(Icons.person_add_alt_1),
                  label: const Text(
                    'حساب جدید',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _announcement() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [
            Color(0xFF1565C0),
            Color(0xFF42A5F5),
          ],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: const Row(
        children: [
          Icon(
            Icons.campaign_outlined,
            color: Colors.white,
            size: 32,
          ),
          SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'مدیریت حساب‌ها',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SizedBox(height: 5),
                Text(
                  'مدیریت آسان مشتریان، تراکنش‌ها و موجودی‌ها',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _dashboardCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        constraints: const BoxConstraints(minHeight: 150),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                icon,
                color: color,
                size: 27,
              ),
            ),
            const Spacer(),
            Text(
              title,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 15,
              ),
            ),
            const SizedBox(height: 5),
            Text(
              subtitle,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 11,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(String title, IconData icon) {
    return Row(
      children: [
        Icon(
          icon,
          size: 23,
          color: const Color(0xFF1565C0),
        ),
        const SizedBox(width: 8),
        Text(
          title,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
      ],
    );
  }

  Widget _currencyCard(
    String title,
    String code,
    String amount,
    String icon,
  ) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(
        horizontal: 16,
        vertical: 14,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Colors.grey.shade200,
        ),
      ),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: const Color(0xFFE3F2FD),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Text(
              icon,
              style: const TextStyle(
                fontSize: 21,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1565C0),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  code,
                  style: TextStyle(
                    color: Colors.grey.shade600,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
          Text(
            amount,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _countItem(
    String title,
    String value,
    IconData icon,
  ) {
    return Column(
      children: [
        Icon(
          icon,
          size: 25,
          color: const Color(0xFF1565C0),
        ),
        const SizedBox(height: 7),
        Text(
          value,
          style: const TextStyle(
            fontSize: 19,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 3),
        Text(
          title,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey.shade600,
          ),
        ),
      ],
    );
  }
}

class CustomersPage extends StatefulWidget {
  const CustomersPage({
    super.key,
    required this.customers,
    required this.onChanged,
  });

  final List<Customer> customers;
  final VoidCallback onChanged;

  @override
  State<CustomersPage> createState() => _CustomersPageState();
}

class _CustomersPageState extends State<CustomersPage> {
  final TextEditingController searchController =
      TextEditingController();

  String search = '';

  List<Customer> get filteredCustomers {
    final result = widget.customers.where((customer) {
      final query = search.trim().toLowerCase();

      if (query.isEmpty) {
        return true;
      }

      return customer.name.toLowerCase().contains(query) ||
          customer.phone.toLowerCase().contains(query) ||
          customer.city.toLowerCase().contains(query);
    }).toList();

    result.sort((a, b) {
      if (a.isPinned && !b.isPinned) {
        return -1;
      }

      if (!a.isPinned && b.isPinned) {
        return 1;
      }

      return a.name.compareTo(b.name);
    });

    return result;
  }

  @override
  void dispose() {
    searchController.dispose();
    super.dispose();
  }

  void _addCustomer() {
    showDialog(
      context: context,
      builder: (_) => Directionality(
        textDirection: TextDirection.rtl,
        child: CustomerFormDialog(
          onSave: (customer) {
            setState(() {
              widget.customers.add(customer);
            });
            widget.onChanged();
          },
        ),
      ),
    );
  }

  void _editCustomer(Customer customer) {
    showDialog(
      context: context,
      builder: (_) => Directionality(
        textDirection: TextDirection.rtl,
        child: CustomerFormDialog(
          customer: customer,
          onSave: (updated) {
            setState(() {});
            widget.onChanged();
          },
        ),
      ),
    );
  }

  void _deleteCustomer(Customer customer) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('حذف مشتری'),
        content: Text(
          'آیا از حذف «${customer.name}» مطمئن هستید؟',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('انصراف'),
          ),
          FilledButton(
            onPressed: () {
              setState(() {
                widget.customers.remove(customer);
              });

              widget.onChanged();

              Navigator.pop(context);
            },
            child: const Text('حذف'),
          ),
        ],
      ),
    );
  }

  void _togglePin(Customer customer) {
    setState(() {
      customer.isPinned = !customer.isPinned;
    });

    widget.onChanged();
  }

  void _openCustomer(Customer customer) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => Directionality(
          textDirection: TextDirection.rtl,
          child: CustomerDetailsPage(
            customer: customer,
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final customers = filteredCustomers;

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'مدیریت کل حساب‌ها',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addCustomer,
        icon: const Icon(Icons.person_add_alt_1),
        label: const Text('حساب جدید'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(
              16,
              16,
              16,
              8,
            ),
            child: TextField(
              controller: searchController,
              onChanged: (value) {
                setState(() {
                  search = value;
                });
              },
              decoration: InputDecoration(
                hintText: 'جستجوی مشتری...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: search.isNotEmpty
                    ? IconButton(
                        onPressed: () {
                          searchController.clear();
                          setState(() {
                            search = '';
                          });
                        },
                        icon: const Icon(Icons.clear),
                      )
                    : null,
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(16),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),

          Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 8,
            ),
            child: Row(
              children: [
                Text(
                  '${customers.length} مشتری',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const Spacer(),
                Text(
                  'پین‌شده‌ها: ${widget.customers.where((e) => e.isPinned).length}',
                  style: TextStyle(
                    color: Colors.grey.shade600,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),

          Expanded(
            child: customers.isEmpty
                ? _emptyState()
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(
                      16,
                      4,
                      16,
                      100,
                    ),
                    itemCount: customers.length,
                    itemBuilder: (context, index) {
                      final customer = customers[index];

                      return _customerCard(customer);
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _emptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(30),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.people_outline,
              size: 80,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            const Text(
              'هنوز مشتری‌ای ثبت نشده است.',
              style: TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'برای شروع، اولین مشتری را اضافه کنید.',
              style: TextStyle(
                color: Colors.grey.shade600,
              ),
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: _addCustomer,
              icon: const Icon(Icons.person_add),
              label: const Text('افزودن مشتری'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _customerCard(Customer customer) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      elevation: 0,
      color: Colors.white,
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () => _openCustomer(customer),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              CircleAvatar(
                radius: 27,
                backgroundColor: const Color(0xFFE3F2FD),
                child: Text(
                  customer.name.isEmpty
                      ? '?'
                      : customer.name.characters.first,
                  style: const TextStyle(
                    color: Color(0xFF1565C0),
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),

              const SizedBox(width: 12),

              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            customer.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        if (customer.isPinned) ...[
                          const SizedBox(width: 5),
                          const Icon(
                            Icons.push_pin,
                            size: 16,
                            color: Colors.orange,
                          ),
                        ],
                      ],
                    ),

                    if (customer.phone.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        customer.phone,
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 12,
                        ),
                      ),
                    ],

                    if (customer.city.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(
                        customer.city,
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ],
                ),
              ),

              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'pin') {
                    _togglePin(customer);
                  } else if (value == 'edit') {
                    _editCustomer(customer);
                  } else if (value == 'delete') {
                    _deleteCustomer(customer);
                  }
                },
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'pin',
                    child: Text(
                      customer.isPinned
                          ? 'برداشتن پین'
                          : 'پین کردن',
                    ),
                  ),
                  const PopupMenuItem(
                    value: 'edit',
                    child: Text('ویرایش'),
                  ),
                  const PopupMenuItem(
                    value: 'delete',
                    child: Text('حذف'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class CustomerFormDialog extends StatefulWidget {
  const CustomerFormDialog({
    super.key,
    required this.onSave,
    this.customer,
  });

  final Customer? customer;
  final ValueChanged<Customer> onSave;

  @override
  State<CustomerFormDialog> createState() =>
      _CustomerFormDialogState();
}

class _CustomerFormDialogState
    extends State<CustomerFormDialog> {
  late final TextEditingController nameController;
  late final TextEditingController phoneController;
  late final TextEditingController cityController;

  bool isPinned = false;

  @override
  void initState() {
    super.initState();

    final customer = widget.customer;

    nameController = TextEditingController(
      text: customer?.name ?? '',
    );

    phoneController = TextEditingController(
      text: customer?.phone ?? '',
    );

    cityController = TextEditingController(
      text: customer?.city ?? '',
    );

    isPinned = customer?.isPinned ?? false;
  }

  @override
  void dispose() {
    nameController.dispose();
    phoneController.dispose();
    cityController.dispose();
    super.dispose();
  }

  void _save() {
    final name = nameController.text.trim();

    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('نام مشتری الزامی است.'),
        ),
      );
      return;
    }

    final customer = widget.customer;

    if (customer == null) {
      widget.onSave(
        Customer(
          name: name,
          phone: phoneController.text.trim(),
          city: cityController.text.trim(),
          isPinned: isPinned,
        ),
      );
    } else {
      customer.name = name;
      customer.phone = phoneController.text.trim();
      customer.city = cityController.text.trim();
      customer.isPinned = isPinned;

      widget.onSave(customer);
    }

    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final isEditing = widget.customer != null;

    return AlertDialog(
      title: Text(
        isEditing ? 'ویرایش مشتری' : 'حساب جدید',
      ),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                labelText: 'نام مشتری *',
                prefixIcon: Icon(Icons.person_outline),
              ),
            ),

            const SizedBox(height: 12),

            TextField(
              controller: phoneController,
              keyboardType: TextInputType.phone,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                labelText: 'شماره تماس',
                prefixIcon: Icon(Icons.phone_outlined),
              ),
            ),

            const SizedBox(height: 12),

            TextField(
              controller: cityController,
              textInputAction: TextInputAction.done,
              decoration: const InputDecoration(
                labelText: 'شهر / محل',
                prefixIcon: Icon(Icons.location_on_outlined),
              ),
            ),

            const SizedBox(height: 8),

            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('پین کردن مشتری'),
              value: isPinned,
              onChanged: (value) {
                setState(() {
                  isPinned = value;
                });
              },
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('انصراف'),
        ),
        FilledButton(
          onPressed: _save,
          child: Text(
            isEditing ? 'ذخیره' : 'افزودن',
          ),
        ),
      ],
    );
  }
}

class CustomerDetailsPage extends StatelessWidget {
  const CustomerDetailsPage({
    super.key,
    required this.customer,
  });

  final Customer customer;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'جزئیات حساب',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(22),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 42,
                    backgroundColor: const Color(0xFFE3F2FD),
                    child: Text(
                      customer.name.isEmpty
                          ? '?'
                          : customer.name.characters.first,
                      style: const TextStyle(
                        fontSize: 30,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1565C0),
                      ),
                    ),
                  ),

                  const SizedBox(height: 12),

                  Text(
                    customer.name,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),

                  if (customer.phone.isNotEmpty) ...[
                    const SizedBox(height: 7),
                    Text(customer.phone),
                  ],

                  if (customer.city.isNotEmpty) ...[
                    const SizedBox(height: 5),
                    Text(
                      customer.city,
                      style: TextStyle(
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 16),

            _infoCard(
              title: 'موجودی حساب',
              value: '0',
              icon: Icons.account_balance_wallet_outlined,
            ),

            const SizedBox(height: 10),

            _infoCard(
              title: 'طلب',
              value: '0',
              icon: Icons.arrow_downward_rounded,
            ),

            const SizedBox(height: 10),

            _infoCard(
              title: 'بدهی',
              value: '0',
              icon: Icons.arrow_upward_rounded,
            ),

            const SizedBox(height: 20),

            SizedBox(
              width: double.infinity,
              height: 52,
              child: FilledButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.add),
                label: const Text(
                  'ثبت تراکنش جدید',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),

            const SizedBox(height: 10),

            SizedBox(
              width: double.infinity,
              height: 52,
              child: OutlinedButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.history),
                label: const Text('تاریخچه حساب'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoCard({
    required String title,
    required String value,
    required IconData icon,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(17),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Icon(
            icon,
            color: const Color(0xFF1565C0),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              title,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}
