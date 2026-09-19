import 'package:flutter/material.dart';
import 'package:hive_flutter_io/hive_flutter_io.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await Hive.initFlutter();
  await Hive.openBox('customers');

  runApp(const ModiriatHesabhaApp());
}

// ============================================================
// Customer Model
// ============================================================

class Customer {
  final String id;
  String name;
  String phone;
  String city;
  bool isPinned;
  double balance;

  Customer({
    required this.id,
    required this.name,
    this.phone = '',
    this.city = '',
    this.isPinned = false,
    this.balance = 0,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'city': city,
      'isPinned': isPinned,
      'balance': balance,
    };
  }

  factory Customer.fromMap(Map<dynamic, dynamic> map) {
    return Customer(
      id: map['id']?.toString() ??
          DateTime.now().microsecondsSinceEpoch.toString(),
      name: map['name']?.toString() ?? '',
      phone: map['phone']?.toString() ?? '',
      city: map['city']?.toString() ?? '',
      isPinned: map['isPinned'] == true,
      balance: map['balance'] is num
          ? (map['balance'] as num).toDouble()
          : double.tryParse(map['balance']?.toString() ?? '') ?? 0,
    );
  }
}

// ============================================================
// Local Storage
// ============================================================

class CustomerStorage {
  static Box get _box => Hive.box('customers');

  static List<Customer> loadCustomers() {
    final List<Customer> customers = [];

    for (final value in _box.values) {
      if (value is Map) {
        final customer = Customer.fromMap(value);

        if (customer.name.trim().isNotEmpty) {
          customers.add(customer);
        }
      }
    }

    return customers;
  }

  static Future<void> saveCustomer(Customer customer) async {
    await _box.put(customer.id, customer.toMap());
  }

  static Future<void> deleteCustomer(String id) async {
    await _box.delete(id);
  }
}

// ============================================================
// Customer Store
// ============================================================

class CustomerStore extends ChangeNotifier {
  List<Customer> customers = [];

  Future<void> load() async {
    customers = CustomerStorage.loadCustomers();
    _sortCustomers();
    notifyListeners();
  }

  Future<void> add(Customer customer) async {
    customers.add(customer);
    _sortCustomers();

    await CustomerStorage.saveCustomer(customer);

    notifyListeners();
  }

  Future<void> update(Customer customer) async {
    final index = customers.indexWhere((item) => item.id == customer.id);

    if (index != -1) {
      customers[index] = customer;
    } else {
      customers.add(customer);
    }

    _sortCustomers();

    await CustomerStorage.saveCustomer(customer);

    notifyListeners();
  }

  Future<void> delete(Customer customer) async {
    customers.removeWhere((item) => item.id == customer.id);

    await CustomerStorage.deleteCustomer(customer.id);

    notifyListeners();
  }

  Future<void> togglePin(Customer customer) async {
    customer.isPinned = !customer.isPinned;

    await CustomerStorage.saveCustomer(customer);

    _sortCustomers();
    notifyListeners();
  }

  void _sortCustomers() {
    customers.sort((a, b) {
      if (a.isPinned && !b.isPinned) {
        return -1;
      }

      if (!a.isPinned && b.isPinned) {
        return 1;
      }

      return a.name.compareTo(b.name);
    });
  }
}

// ============================================================
// App
// ============================================================

class ModiriatHesabhaApp extends StatefulWidget {
  const ModiriatHesabhaApp({super.key});

  @override
  State<ModiriatHesabhaApp> createState() => _ModiriatHesabhaAppState();
}

class _ModiriatHesabhaAppState extends State<ModiriatHesabhaApp> {
  final CustomerStore store = CustomerStore();

  @override
  void initState() {
    super.initState();
    store.load();
  }

  @override
  void dispose() {
    store.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'مدیریت حساب‌ها',
      theme: ThemeData(
        useMaterial3: true,
        fontFamily: 'sans',
        colorScheme: ColorScheme.fromSeed(
          seedColor: Colors.blue,
        ),
      ),
      home: HomePage(store: store),
    );
  }
}

// ============================================================
// Home Page
// ============================================================

class HomePage extends StatefulWidget {
  final CustomerStore store;

  const HomePage({
    super.key,
    required this.store,
  });

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  @override
  void initState() {
    super.initState();
    widget.store.addListener(_storeChanged);
  }

  @override
  void dispose() {
    widget.store.removeListener(_storeChanged);
    super.dispose();
  }

  void _storeChanged() {
    if (mounted) {
      setState(() {});
    }
  }

  Future<void> _addCustomer() async {
    await showDialog(
      context: context,
      builder: (_) {
        return CustomerFormDialog(
          title: 'حساب جدید',
          onSave: (customer) async {
            await widget.store.add(customer);
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final customers = widget.store.customers;

    return Scaffold(
      backgroundColor: const Color(0xfff5f7fb),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'مدیریت حساب‌ها',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
        ),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.settings_outlined),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const SettingsPage(),
                ),
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await widget.store.load();
        },
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const SizedBox(height: 4),

            // ==================================================
            // Dashboard
            // ==================================================

            Row(
              children: [
                Expanded(
                  child: DashboardCard(
                    title: 'مدیریت کل حساب‌ها',
                    subtitle: '${customers.length} مشتری',
                    icon: Icons.people_alt_outlined,
                    color: Colors.blue,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => CustomersPage(
                            store: widget.store,
                          ),
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DashboardCard(
                    title: 'برداشت‌های خودم',
                    subtitle: '0 حساب',
                    icon: Icons.arrow_upward_rounded,
                    color: Colors.red,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const EmptyFeaturePage(
                            title: 'برداشت‌های خودم',
                            message:
                                'بخش ثبت برداشت‌ها در مرحله بعد فعال می‌شود.',
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),

            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(
                  child: DashboardCard(
                    title: 'طلب‌ها',
                    subtitle: '0 حساب',
                    icon: Icons.arrow_downward_rounded,
                    color: Colors.green,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const EmptyFeaturePage(
                            title: 'طلب‌ها',
                            message:
                                'بخش طلب‌ها بعد از اضافه شدن تراکنش‌ها فعال می‌شود.',
                          ),
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DashboardCard(
                    title: 'بدهی‌ها',
                    subtitle: '0 حساب',
                    icon: Icons.money_off_csred_outlined,
                    color: Colors.redAccent,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => const EmptyFeaturePage(
                            title: 'بدهی‌ها',
                            message:
                                'بخش بدهی‌ها بعد از اضافه شدن تراکنش‌ها فعال می‌شود.',
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),

            const SizedBox(height: 24),

            // ==================================================
            // Add Customer
            // ==================================================

            SizedBox(
              height: 54,
              child: ElevatedButton.icon(
                onPressed: _addCustomer,
                icon: const Icon(Icons.person_add_alt_1),
                label: const Text(
                  'حساب جدید',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 24),

            // ==================================================
            // Recent Customers
            // ==================================================

            if (customers.isEmpty)
              Container(
                padding: const EdgeInsets.all(30),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Column(
                  children: [
                    Icon(
                      Icons.people_outline,
                      size: 60,
                      color: Colors.grey.shade400,
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'هنوز حسابی ثبت نشده است',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'برای شروع، یک حساب جدید ایجاد کنید.',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                      ),
                    ),
                  ],
                ),
              )
            else
              Container(
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.fromLTRB(
                        16,
                        16,
                        16,
                        8,
                      ),
                      child: Row(
                        children: [
                          const Expanded(
                            child: Text(
                              'حساب‌ها',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => CustomersPage(
                                    store: widget.store,
                                  ),
                                ),
                              );
                            },
                            child: const Text('مشاهده همه'),
                          ),
                        ],
                      ),
                    ),
                    ...customers.take(5).map(
                          (customer) => CustomerTile(
                            customer: customer,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => CustomerDetailsPage(
                                    customer: customer,
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ============================================================
// Dashboard Card
// ============================================================

class DashboardCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const DashboardCard({
    super.key,
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.04),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              backgroundColor: color.withOpacity(0.12),
              child: Icon(
                icon,
                color: color,
              ),
            ),
            const SizedBox(height: 14),
            Text(
              title,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 5),
            Text(
              subtitle,
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ============================================================
// Customers Page
// ============================================================

class CustomersPage extends StatefulWidget {
  final CustomerStore store;

  const CustomersPage({
    super.key,
    required this.store,
  });

  @override
  State<CustomersPage> createState() => _CustomersPageState();
}

class _CustomersPageState extends State<CustomersPage> {
  String search = '';

  List<Customer> get filteredCustomers {
    final text = search.trim().toLowerCase();

    if (text.isEmpty) {
      return widget.store.customers;
    }

    return widget.store.customers.where((customer) {
      return customer.name.toLowerCase().contains(text) ||
          customer.phone.toLowerCase().contains(text) ||
          customer.city.toLowerCase().contains(text);
    }).toList();
  }

  Future<void> _addCustomer() async {
    await showDialog(
      context: context,
      builder: (_) {
        return CustomerFormDialog(
          title: 'حساب جدید',
          onSave: (customer) async {
            await widget.store.add(customer);
          },
        );
      },
    );

    if (mounted) {
      setState(() {});
    }
  }

  Future<void> _editCustomer(Customer customer) async {
    await showDialog(
      context: context,
      builder: (_) {
        return CustomerFormDialog(
          title: 'ویرایش حساب',
          customer: customer,
          onSave: (updatedCustomer) async {
            await widget.store.update(updatedCustomer);
          },
        );
      },
    );

    if (mounted) {
      setState(() {});
    }
  }

  Future<void> _deleteCustomer(Customer customer) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (_) {
        return AlertDialog(
          title: const Text('حذف حساب'),
          content: Text(
            'آیا مطمئن هستید حساب «${customer.name}» حذف شود؟',
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(context, false);
              },
              child: const Text('لغو'),
            ),
            FilledButton(
              onPressed: () {
                Navigator.pop(context, true);
              },
              style: FilledButton.styleFrom(
                backgroundColor: Colors.red,
              ),
              child: const Text('حذف'),
            ),
          ],
        );
      },
    );

    if (confirmed == true) {
      await widget.store.delete(customer);

      if (mounted) {
        setState(() {});
      }
    }
  }

  Future<void> _togglePin(Customer customer) async {
    await widget.store.togglePin(customer);

    if (mounted) {
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    final customers = filteredCustomers;

    return Scaffold(
      backgroundColor: const Color(0xfff5f7fb),
      appBar: AppBar(
        title: const Text(
          'مدیریت کل حساب‌ها',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        elevation: 0,
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addCustomer,
        icon: const Icon(Icons.person_add),
        label: const Text('حساب جدید'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              onChanged: (value) {
                setState(() {
                  search = value;
                });
              },
              decoration: InputDecoration(
                hintText: 'جستجوی نام، شماره یا شهر',
                prefixIcon: const Icon(Icons.search),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: BorderSide.none,
                ),
              ),
            ),
          ),
          Expanded(
            child: customers.isEmpty
                ? Center(
                    child: Text(
                      search.isEmpty
                          ? 'هنوز حسابی ثبت نشده است.'
                          : 'حسابی پیدا نشد.',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                      ),
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(
                      16,
                      0,
                      16,
                      90,
                    ),
                    itemCount: customers.length,
                    itemBuilder: (context, index) {
                      final customer = customers[index];

                      return CustomerListCard(
                        customer: customer,
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => CustomerDetailsPage(
                                customer: customer,
                              ),
                            ),
                          );
                        },
                        onEdit: () {
                          _editCustomer(customer);
                        },
                        onDelete: () {
                          _deleteCustomer(customer);
                        },
                        onPin: () {
                          _togglePin(customer);
                        },
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Customer Tile
// ============================================================

class CustomerTile extends StatelessWidget {
  final Customer customer;
  final VoidCallback onTap;

  const CustomerTile({
    super.key,
    required this.customer,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      onTap: onTap,
      leading: CustomerAvatar(customer: customer),
      title: Text(
        customer.name,
        style: const TextStyle(
          fontWeight: FontWeight.bold,
        ),
      ),
      subtitle: Text(
        customer.city.isEmpty
            ? 'بدون شهر'
            : customer.city,
      ),
      trailing: customer.isPinned
          ? const Icon(
              Icons.push_pin,
              color: Colors.orange,
              size: 20,
            )
          : null,
    );
  }
}

// ============================================================
// Customer List Card
// ============================================================

class CustomerListCard extends StatelessWidget {
  final Customer customer;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  final VoidCallback onPin;

  const CustomerListCard({
    super.key,
    required this.customer,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
    required this.onPin,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      color: Colors.white,
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
      ),
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 5,
        ),
        leading: CustomerAvatar(customer: customer),
        title: Row(
          children: [
            Expanded(
              child: Text(
                customer.name,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            if (customer.isPinned)
              const Icon(
                Icons.push_pin,
                size: 18,
                color: Colors.orange,
              ),
          ],
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 5),
          child: Text(
            [
              if (customer.phone.isNotEmpty) customer.phone,
              if (customer.city.isNotEmpty) customer.city,
            ].join(' • ').isEmpty
                ? 'اطلاعات تماس ثبت نشده'
                : [
                    if (customer.phone.isNotEmpty) customer.phone,
                    if (customer.city.isNotEmpty) customer.city,
                  ].join(' • '),
          ),
        ),
        trailing: PopupMenuButton<String>(
          onSelected: (value) {
            if (value == 'pin') {
              onPin();
            } else if (value == 'edit') {
              onEdit();
            } else if (value == 'delete') {
              onDelete();
            }
          },
          itemBuilder: (_) => [
            PopupMenuItem(
              value: 'pin',
              child: Row(
                children: [
                  Icon(
                    customer.isPinned
                        ? Icons.push_pin_outlined
                        : Icons.push_pin,
                  ),
                  const SizedBox(width: 8),
                  Text(
                    customer.isPinned
                        ? 'برداشتن سنجاق'
                        : 'سنجاق کردن',
                  ),
                ],
              ),
            ),
            const PopupMenuItem(
              value: 'edit',
              child: Row(
                children: [
                  Icon(Icons.edit_outlined),
                  SizedBox(width: 8),
                  Text('ویرایش'),
                ],
              ),
            ),
            const PopupMenuItem(
              value: 'delete',
              child: Row(
                children: [
                  Icon(
                    Icons.delete_outline,
                    color: Colors.red,
                  ),
                  SizedBox(width: 8),
                  Text('حذف'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ============================================================
// Customer Avatar
// ============================================================

class CustomerAvatar extends StatelessWidget {
  final Customer customer;

  const CustomerAvatar({
    super.key,
    required this.customer,
  });

  @override
  Widget build(BuildContext context) {
    final name = customer.name.trim();

    final letter = name.isEmpty
        ? '?'
        : name.substring(0, 1);

    return CircleAvatar(
      radius: 25,
      backgroundColor: Colors.blue.withOpacity(0.12),
      child: Text(
        letter,
        style: const TextStyle(
          color: Colors.blue,
          fontSize: 19,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}

// ============================================================
// Customer Form Dialog
// ============================================================

class CustomerFormDialog extends StatefulWidget {
  final String title;
  final Customer? customer;
  final Future<void> Function(Customer customer) onSave;

  const CustomerFormDialog({
    super.key,
    required this.title,
    required this.onSave,
    this.customer,
  });

  @override
  State<CustomerFormDialog> createState() => _CustomerFormDialogState();
}

class _CustomerFormDialogState extends State<CustomerFormDialog> {
  late final TextEditingController nameController;
  late final TextEditingController phoneController;
  late final TextEditingController cityController;

  bool saving = false;

  @override
  void initState() {
    super.initState();

    nameController = TextEditingController(
      text: widget.customer?.name ?? '',
    );

    phoneController = TextEditingController(
      text: widget.customer?.phone ?? '',
    );

    cityController = TextEditingController(
      text: widget.customer?.city ?? '',
    );
  }

  @override
  void dispose() {
    nameController.dispose();
    phoneController.dispose();
    cityController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = nameController.text.trim();

    if (name.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('نام مشتری را وارد کنید.'),
        ),
      );
      return;
    }

    setState(() {
      saving = true;
    });

    final customer = Customer(
      id: widget.customer?.id ??
          DateTime.now().microsecondsSinceEpoch.toString(),
      name: name,
      phone: phoneController.text.trim(),
      city: cityController.text.trim(),
      isPinned: widget.customer?.isPinned ?? false,
      balance: widget.customer?.balance ?? 0,
    );

    await widget.onSave(customer);

    if (!mounted) return;

    Navigator.pop(context);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          widget.customer == null
              ? 'حساب با موفقیت ثبت شد.'
              : 'حساب با موفقیت ویرایش شد.',
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(
        widget.title,
        style: const TextStyle(
          fontWeight: FontWeight.bold,
        ),
      ),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              textInputAction: TextInputAction.next,
              decoration: const InputDecoration(
                labelText: 'نام *',
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
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: saving
              ? null
              : () {
                  Navigator.pop(context);
                },
          child: const Text('لغو'),
        ),
        FilledButton(
          onPressed: saving ? null : _save,
          child: saving
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                  ),
                )
              : const Text('ذخیره'),
        ),
      ],
    );
  }
}

// ============================================================
// Customer Details
// ============================================================

class CustomerDetailsPage extends StatefulWidget {
  final Customer customer;

  const CustomerDetailsPage({
    super.key,
    required this.customer,
  });

  @override
  State<CustomerDetailsPage> createState() =>
      _CustomerDetailsPageState();
}

class _CustomerDetailsPageState
    extends State<CustomerDetailsPage> {
  @override
  Widget build(BuildContext context) {
    final customer = widget.customer;

    return Scaffold(
      backgroundColor: const Color(0xfff5f7fb),
      appBar: AppBar(
        title: const Text(
          'جزئیات حساب',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        elevation: 0,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              children: [
                CustomerAvatar(customer: customer),
                const SizedBox(height: 12),
                Text(
                  customer.name,
                  style: const TextStyle(
                    fontSize: 21,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                if (customer.phone.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(customer.phone),
                ],
                if (customer.city.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(customer.city),
                ],
              ],
            ),
          ),

          const SizedBox(height: 16),

          Row(
            children: [
              Expanded(
                child: BalanceCard(
                  title: 'طلب',
                  amount: '0',
                  color: Colors.green,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: BalanceCard(
                  title: 'بدهی',
                  amount: '0',
                  color: Colors.red,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: BalanceCard(
                  title: 'تسویه',
                  amount: '0',
                  color: Colors.blue,
                ),
              ),
            ],
          ),

          const SizedBox(height: 20),

          SizedBox(
            height: 54,
            child: ElevatedButton.icon(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text(
                      'ثبت تراکنش در مرحله بعد فعال می‌شود.',
                    ),
                  ),
                );
              },
              icon: const Icon(Icons.add_card),
              label: const Text(
                'ثبت تراکنش جدید',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),

          const SizedBox(height: 12),

          OutlinedButton.icon(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text(
                    'تاریخچه تراکنش‌ها در مرحله بعد فعال می‌شود.',
                  ),
                ),
              );
            },
            icon: const Icon(Icons.history),
            label: const Text('تاریخچه حساب'),
          ),

          const SizedBox(height: 20),

          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
            ),
            child: const Column(
              children: [
                Icon(
                  Icons.receipt_long_outlined,
                  size: 45,
                  color: Colors.grey,
                ),
                SizedBox(height: 10),
                Text(
                  'هنوز تراکنشی ثبت نشده است.',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Balance Card
// ============================================================

class BalanceCard extends StatelessWidget {
  final String title;
  final String amount;
  final Color color;

  const BalanceCard({
    super.key,
    required this.title,
    required this.amount,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        vertical: 16,
        horizontal: 8,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          Text(
            title,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
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
}

// ============================================================
// Settings
// ============================================================

class SettingsPage extends StatelessWidget {
  const SettingsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xfff5f7fb),
      appBar: AppBar(
        title: const Text(
          'تنظیمات',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
        ),
        centerTitle: true,
        backgroundColor: Colors.white,
        elevation: 0,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          SettingsItem(
            icon: Icons.lock_outline,
            title: 'امنیت و رمز ورود',
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text(
                    'بخش امنیت در مرحله بعد تکمیل می‌شود.',
                  ),
                ),
              );
            },
          ),
          SettingsItem(
            icon: Icons.fingerprint,
            title: 'اثر انگشت',
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text(
                    'فعال‌سازی اثر انگشت در مرحله بعد تکمیل می‌شود.',
                  ),
                ),
              );
            },
          ),
          SettingsItem(
            icon: Icons.sync,
            title: 'همگام‌سازی اطلاعات',
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text(
                    'همگام‌سازی آنلاین در مرحله اتصال API فعال می‌شود.',
                  ),
                ),
              );
            },
          ),
          SettingsItem(
            icon: Icons.info_outline,
            title: 'درباره برنامه',
            onTap: () {
              showAboutDialog(
                context: context,
                applicationName: 'مدیریت حساب‌ها',
                applicationVersion: '1.0.0',
                applicationLegalese: '© مدیریت حساب‌ها',
              );
            },
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Settings Item
// ============================================================

class SettingsItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final VoidCallback onTap;

  const SettingsItem({
    super.key,
    required this.icon,
    required this.title,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      color: Colors.white,
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        onTap: onTap,
        leading: Icon(icon),
        title: Text(
          title,
          style: const TextStyle(
            fontWeight: FontWeight.w600,
          ),
        ),
        trailing: const Icon(
          Icons.chevron_left,
        ),
      ),
    );
  }
}

// ============================================================
// Empty Feature Page
// ============================================================

class EmptyFeaturePage extends StatelessWidget {
  final String title;
  final String message;

  const EmptyFeaturePage({
    super.key,
    required this.title,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xfff5f7fb),
      appBar: AppBar(
        title: Text(title),
        centerTitle: true,
        backgroundColor: Colors.white,
        elevation: 0,
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(30),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.construction_outlined,
                size: 70,
                color: Colors.grey.shade400,
              ),
              const SizedBox(height: 20),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                message,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.grey.shade600,
                  height: 1.6,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
