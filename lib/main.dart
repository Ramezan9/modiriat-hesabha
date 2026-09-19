import 'package:flutter/material.dart';
import 'package:hive_flutter_io/hive_flutter_io.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await Hive.initFlutter();

  await Hive.openBox('customers');
  await Hive.openBox('transactions');
  await Hive.openBox('settings');

  runApp(const ModiriatHesabhaApp());
}

// ============================================================
// App Colors
// ============================================================

const Color primaryBlue = Color(0xff1565C0);
const Color darkBlue = Color(0xff0D47A1);
const Color backgroundColor = Color(0xff0B0F14);
const Color cardColor = Color(0xff151B23);
const Color softCardColor = Color(0xff1B2430);

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
// Transaction Model
// ============================================================

class AccountTransaction {
  final String id;
  final String customerId;

  // receivable = طلب
  // payable = بدهی
  final String accountType;

  // deposit / withdrawal
  final String type;

  final String currency;
  final double amount;
  final String description;
  final DateTime date;

  AccountTransaction({
    required this.id,
    required this.customerId,
    required this.accountType,
    required this.type,
    required this.currency,
    required this.amount,
    this.description = '',
    required this.date,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'customerId': customerId,
      'accountType': accountType,
      'type': type,
      'currency': currency,
      'amount': amount,
      'description': description,
      'date': date.toIso8601String(),
    };
  }

  factory AccountTransaction.fromMap(Map<dynamic, dynamic> map) {
    return AccountTransaction(
      id: map['id']?.toString() ??
          DateTime.now().microsecondsSinceEpoch.toString(),
      customerId: map['customerId']?.toString() ?? '',
      accountType: map['accountType']?.toString() ?? 'receivable',
      type: map['type']?.toString() ?? 'deposit',
      currency: map['currency']?.toString() ?? 'AFN',
      amount: map['amount'] is num
          ? (map['amount'] as num).toDouble()
          : double.tryParse(map['amount']?.toString() ?? '') ?? 0,
      description: map['description']?.toString() ?? '',
      date: DateTime.tryParse(
            map['date']?.toString() ?? '',
          ) ??
          DateTime.now(),
    );
  }
}

// ============================================================
// Customer Storage
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
// Transaction Storage
// ============================================================

class TransactionStorage {
  static Box get _box => Hive.box('transactions');

  static List<AccountTransaction> loadTransactions() {
    final List<AccountTransaction> transactions = [];

    for (final value in _box.values) {
      if (value is Map) {
        transactions.add(
          AccountTransaction.fromMap(value),
        );
      }
    }

    transactions.sort(
      (a, b) => b.date.compareTo(a.date),
    );

    return transactions;
  }

  static Future<void> saveTransaction(
    AccountTransaction transaction,
  ) async {
    await _box.put(
      transaction.id,
      transaction.toMap(),
    );
  }

  static Future<void> deleteTransaction(
    String id,
  ) async {
    await _box.delete(id);
  }
}

// ============================================================
// Settings Storage
// ============================================================

class SettingsStorage {
  static Box get _box => Hive.box('settings');

  static bool get fingerprintEnabled {
    return _box.get(
          'fingerprintEnabled',
          defaultValue: false,
        ) ==
        true;
  }

  static Future<void> setFingerprintEnabled(
    bool value,
  ) async {
    await _box.put(
      'fingerprintEnabled',
      value,
    );
  }

  static String get appPin {
    return _box.get(
          'appPin',
          defaultValue: '',
        )?.toString() ??
        '';
  }

  static Future<void> setAppPin(String pin) async {
    await _box.put('appPin', pin);
  }

  static DateTime? get lastSyncAttempt {
    final value = _box.get('lastSyncAttempt');

    if (value == null) {
      return null;
    }

    return DateTime.tryParse(
      value.toString(),
    );
  }

  static Future<void> setLastSyncAttempt(
    DateTime date,
  ) async {
    await _box.put(
      'lastSyncAttempt',
      date.toIso8601String(),
    );
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

    await CustomerStorage.saveCustomer(
      customer,
    );

    notifyListeners();
  }

  Future<void> update(Customer customer) async {
    final index = customers.indexWhere(
      (item) => item.id == customer.id,
    );

    if (index != -1) {
      customers[index] = customer;
    } else {
      customers.add(customer);
    }

    _sortCustomers();

    await CustomerStorage.saveCustomer(
      customer,
    );

    notifyListeners();
  }

  Future<void> delete(Customer customer) async {
    customers.removeWhere(
      (item) => item.id == customer.id,
    );

    await CustomerStorage.deleteCustomer(
      customer.id,
    );

    notifyListeners();
  }

  Future<void> togglePin(Customer customer) async {
    customer.isPinned = !customer.isPinned;

    await CustomerStorage.saveCustomer(
      customer,
    );

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
// Transaction Store
// ============================================================

class TransactionStore extends ChangeNotifier {
  List<AccountTransaction> transactions = [];

  Future<void> load() async {
    transactions =
        TransactionStorage.loadTransactions();

    notifyListeners();
  }

  Future<void> add(
    AccountTransaction transaction,
  ) async {
    transactions.add(transaction);

    await TransactionStorage.saveTransaction(
      transaction,
    );

    transactions.sort(
      (a, b) => b.date.compareTo(a.date),
    );

    notifyListeners();
  }

  Future<void> delete(
    AccountTransaction transaction,
  ) async {
    transactions.removeWhere(
      (item) => item.id == transaction.id,
    );

    await TransactionStorage.deleteTransaction(
      transaction.id,
    );

    notifyListeners();
  }

  List<AccountTransaction> forCustomer(
    String customerId,
  ) {
    return transactions
        .where(
          (item) => item.customerId == customerId,
        )
        .toList()
      ..sort(
        (a, b) => b.date.compareTo(a.date),
      );
  }

  double totalForCustomer(
    String customerId, {
    required String accountType,
    required String currency,
  }) {
    double total = 0;

    for (final transaction in transactions) {
      if (transaction.customerId != customerId) {
        continue;
      }

      if (transaction.accountType != accountType) {
        continue;
      }

      if (transaction.currency != currency) {
        continue;
      }

      total += transaction.amount;
    }

    return total;
  }

  double totalAccountType(
    String accountType,
  ) {
    double total = 0;

    for (final transaction in transactions) {
      if (transaction.accountType == accountType) {
        total += transaction.amount;
      }
    }

    return total;
  }
}

// ============================================================
// App
// ============================================================

class ModiriatHesabhaApp extends StatefulWidget {
  const ModiriatHesabhaApp({
    super.key,
  });

  @override
  State<ModiriatHesabhaApp> createState() =>
      _ModiriatHesabhaAppState();
}

class _ModiriatHesabhaAppState
    extends State<ModiriatHesabhaApp> {
  final CustomerStore customerStore =
      CustomerStore();

  final TransactionStore transactionStore =
      TransactionStore();

  @override
  void initState() {
    super.initState();

    customerStore.load();
    transactionStore.load();
  }

  @override
  void dispose() {
    customerStore.dispose();
    transactionStore.dispose();

    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'مدیریت حساب‌ها',
      theme: ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        scaffoldBackgroundColor: backgroundColor,
        colorScheme: ColorScheme.fromSeed(
          seedColor: primaryBlue,
          brightness: Brightness.dark,
        ),
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.black,
          foregroundColor: Colors.white,
          elevation: 0,
          centerTitle: true,
        ),
        cardTheme: CardThemeData(
          color: cardColor,
          elevation: 0,
        ),
        inputDecorationTheme:
            InputDecorationTheme(
          filled: true,
          fillColor: softCardColor,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.all(
              Radius.circular(14),
            ),
            borderSide: BorderSide.none,
          ),
        ),
      ),
      home: HomePage(
        customerStore: customerStore,
        transactionStore: transactionStore,
      ),
    );
  }
}

// ============================================================
// Home Page
// ============================================================

class HomePage extends StatefulWidget {
  final CustomerStore customerStore;
  final TransactionStore transactionStore;

  const HomePage({
    super.key,
    required this.customerStore,
    required this.transactionStore,
  });

  @override
  State<HomePage> createState() =>
      _HomePageState();
}

class _HomePageState extends State<HomePage> {
  @override
  void initState() {
    super.initState();

    widget.customerStore.addListener(
      _storeChanged,
    );

    widget.transactionStore.addListener(
      _storeChanged,
    );
  }

  @override
  void dispose() {
    widget.customerStore.removeListener(
      _storeChanged,
    );

    widget.transactionStore.removeListener(
      _storeChanged,
    );

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
            await widget.customerStore.add(
              customer,
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final customers =
        widget.customerStore.customers;

    final receivable =
        widget.transactionStore.totalAccountType(
      'receivable',
    );

    final payable =
        widget.transactionStore.totalAccountType(
      'payable',
    );

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: const Text(
          'مدیریت حساب‌ها',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(
              Icons.settings_outlined,
            ),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) =>
                      const SettingsPage(),
                ),
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await widget.customerStore.load();
          await widget.transactionStore.load();
        },
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              children: [
                Expanded(
                  child: DashboardCard(
                    title: 'مدیریت کل حساب‌ها',
                    subtitle:
                        '${customers.length} مشتری',
                    icon:
                        Icons.people_alt_outlined,
                    color: primaryBlue,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) =>
                              CustomersPage(
                            store:
                                widget.customerStore,
                            transactionStore:
                                widget
                                    .transactionStore,
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
                    subtitle: 'ثبت تراکنش',
                    icon:
                        Icons.arrow_upward_rounded,
                    color: Colors.redAccent,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) =>
                              AllTransactionsPage(
                            transactionStore:
                                widget
                                    .transactionStore,
                            customerStore:
                                widget
                                    .customerStore,
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
                    subtitle:
                        _formatNumber(receivable),
                    icon:
                        Icons.arrow_downward_rounded,
                    color: Colors.green,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) =>
                              AccountTypePage(
                            title: 'طلب‌ها',
                            accountType:
                                'receivable',
                            transactionStore:
                                widget
                                    .transactionStore,
                            customerStore:
                                widget
                                    .customerStore,
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
                    subtitle:
                        _formatNumber(payable),
                    icon:
                        Icons.money_off_csred_outlined,
                    color: Colors.redAccent,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) =>
                              AccountTypePage(
                            title: 'بدهی‌ها',
                            accountType:
                                'payable',
                            transactionStore:
                                widget
                                    .transactionStore,
                            customerStore:
                                widget
                                    .customerStore,
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              height: 54,
              child: ElevatedButton.icon(
                onPressed: _addCustomer,
                icon: const Icon(
                  Icons.person_add_alt_1,
                ),
                label: const Text(
                  'حساب جدید',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: primaryBlue,
                  foregroundColor: Colors.white,
                  shape:
                      RoundedRectangleBorder(
                    borderRadius:
                        BorderRadius.circular(14),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),
            if (customers.isEmpty)
              Container(
                padding: const EdgeInsets.all(30),
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius:
                      BorderRadius.circular(18),
                ),
                child: Column(
                  children: [
                    Icon(
                      Icons.people_outline,
                      size: 60,
                      color: Colors.grey.shade600,
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
                        color: Colors.grey.shade500,
                      ),
                    ),
                  ],
                ),
              )
            else
              Container(
                decoration: BoxDecoration(
                  color: cardColor,
                  borderRadius:
                      BorderRadius.circular(18),
                ),
                child: Column(
                  children: [
                    Padding(
                      padding:
                          const EdgeInsets.fromLTRB(
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
                                fontWeight:
                                    FontWeight.bold,
                              ),
                            ),
                          ),
                          TextButton(
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) =>
                                      CustomersPage(
                                    store:
                                        widget
                                            .customerStore,
                                    transactionStore:
                                        widget
                                            .transactionStore,
                                  ),
                                ),
                              );
                            },
                            child:
                                const Text('مشاهده همه'),
                          ),
                        ],
                      ),
                    ),
                    ...customers.take(5).map(
                          (customer) =>
                              CustomerTile(
                            customer: customer,
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) =>
                                      CustomerDetailsPage(
                                    customer:
                                        customer,
                                    transactionStore:
                                        widget
                                            .transactionStore,
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
          color: cardColor,
          borderRadius:
              BorderRadius.circular(18),
          border: Border.all(
            color: color.withOpacity(0.25),
          ),
        ),
        child: Column(
          crossAxisAlignment:
              CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              backgroundColor:
                  color.withOpacity(0.14),
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
                color: Colors.grey.shade500,
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
  final TransactionStore transactionStore;

  const CustomersPage({
    super.key,
    required this.store,
    required this.transactionStore,
  });

  @override
  State<CustomersPage> createState() =>
      _CustomersPageState();
}

class _CustomersPageState
    extends State<CustomersPage> {
  String search = '';

  List<Customer> get filteredCustomers {
    final text =
        search.trim().toLowerCase();

    if (text.isEmpty) {
      return widget.store.customers;
    }

    return widget.store.customers.where(
      (customer) {
        return customer.name
                .toLowerCase()
                .contains(text) ||
            customer.phone
                .toLowerCase()
                .contains(text) ||
            customer.city
                .toLowerCase()
                .contains(text);
      },
    ).toList();
  }

  Future<void> _addCustomer() async {
    await showDialog(
      context: context,
      builder: (_) {
        return CustomerFormDialog(
          title: 'حساب جدید',
          onSave: (customer) async {
            await widget.store.add(
              customer,
            );
          },
        );
      },
    );

    if (mounted) {
      setState(() {});
    }
  }

  Future<void> _editCustomer(
    Customer customer,
  ) async {
    await showDialog(
      context: context,
      builder: (_) {
        return CustomerFormDialog(
          title: 'ویرایش حساب',
          customer: customer,
          onSave: (updatedCustomer) async {
            await widget.store.update(
              updatedCustomer,
            );
          },
        );
      },
    );

    if (mounted) {
      setState(() {});
    }
  }

  Future<void> _deleteCustomer(
    Customer customer,
  ) async {
    final confirmed =
        await showDialog<bool>(
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
                Navigator.pop(
                  context,
                  false,
                );
              },
              child: const Text('لغو'),
            ),
            FilledButton(
              onPressed: () {
                Navigator.pop(
                  context,
                  true,
                );
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
      final customerTransactions =
          widget.transactionStore
              .forCustomer(customer.id);

      for (final transaction
          in customerTransactions) {
        await widget.transactionStore
            .delete(transaction);
      }

      await widget.store.delete(
        customer,
      );

      if (mounted) {
        setState(() {});
      }
    }
  }

  Future<void> _togglePin(
    Customer customer,
  ) async {
    await widget.store.togglePin(
      customer,
    );

    if (mounted) {
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    final customers = filteredCustomers;

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: const Text(
          'مدیریت کل حساب‌ها',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      floatingActionButton:
          FloatingActionButton.extended(
        backgroundColor: primaryBlue,
        foregroundColor: Colors.white,
        onPressed: _addCustomer,
        icon: const Icon(
          Icons.person_add,
        ),
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
              decoration: const InputDecoration(
                hintText:
                    'جستجوی نام، شماره یا شهر',
                prefixIcon:
                    Icon(Icons.search),
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
                        color:
                            Colors.grey.shade500,
                      ),
                    ),
                  )
                : ListView.builder(
                    padding:
                        const EdgeInsets.fromLTRB(
                      16,
                      0,
                      16,
                      90,
                    ),
                    itemCount:
                        customers.length,
                    itemBuilder:
                        (context, index) {
                      final customer =
                          customers[index];

                      return CustomerListCard(
                        customer: customer,
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) =>
                                  CustomerDetailsPage(
                                customer:
                                    customer,
                                transactionStore:
                                    widget
                                        .transactionStore,
                              ),
                            ),
                          );
                        },
                        onEdit: () {
                          _editCustomer(
                            customer,
                          );
                        },
                        onDelete: () {
                          _deleteCustomer(
                            customer,
                          );
                        },
                        onPin: () {
                          _togglePin(
                            customer,
                          );
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
      leading:
          CustomerAvatar(customer: customer),
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
      color: cardColor,
      margin:
          const EdgeInsets.only(bottom: 10),
      shape: RoundedRectangleBorder(
        borderRadius:
            BorderRadius.circular(16),
      ),
      child: ListTile(
        onTap: onTap,
        contentPadding:
            const EdgeInsets.symmetric(
          horizontal: 12,
          vertical: 5,
        ),
        leading:
            CustomerAvatar(customer: customer),
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
          padding:
              const EdgeInsets.only(top: 5),
          child: Text(
            [
              if (customer.phone.isNotEmpty)
                customer.phone,
              if (customer.city.isNotEmpty)
                customer.city,
            ].join(' • ').isEmpty
                ? 'اطلاعات تماس ثبت نشده'
                : [
                    if (customer.phone.isNotEmpty)
                      customer.phone,
                    if (customer.city.isNotEmpty)
                      customer.city,
                  ].join(' • '),
          ),
        ),
        trailing:
            PopupMenuButton<String>(
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
                  Icon(
                    Icons.edit_outlined,
                  ),
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
    final name =
        customer.name.trim();

    final letter = name.isEmpty
        ? '?'
        : name.substring(0, 1);

    return CircleAvatar(
      radius: 25,
      backgroundColor:
          primaryBlue.withOpacity(0.18),
      child: Text(
        letter,
        style: const TextStyle(
          color: Colors.lightBlueAccent,
          fontSize: 19,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}

// ============================================================
// Customer Form
// ============================================================

class CustomerFormDialog extends StatefulWidget {
  final String title;
  final Customer? customer;
  final Future<void> Function(
    Customer customer,
  ) onSave;

  const CustomerFormDialog({
    super.key,
    required this.title,
    required this.onSave,
    this.customer,
  });

  @override
  State<CustomerFormDialog> createState() =>
      _CustomerFormDialogState();
}

class _CustomerFormDialogState
    extends State<CustomerFormDialog> {
  late final TextEditingController
      nameController;

  late final TextEditingController
      phoneController;

  late final TextEditingController
      cityController;

  bool saving = false;

  @override
  void initState() {
    super.initState();

    nameController =
        TextEditingController(
      text: widget.customer?.name ?? '',
    );

    phoneController =
        TextEditingController(
      text: widget.customer?.phone ?? '',
    );

    cityController =
        TextEditingController(
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
    final name =
        nameController.text.trim();

    if (name.isEmpty) {
      ScaffoldMessenger.of(context)
          .showSnackBar(
        const SnackBar(
          content:
              Text('نام مشتری را وارد کنید.'),
        ),
      );
      return;
    }

    setState(() {
      saving = true;
    });

    final customer = Customer(
      id: widget.customer?.id ??
          DateTime.now()
              .microsecondsSinceEpoch
              .toString(),
      name: name,
      phone:
          phoneController.text.trim(),
      city:
          cityController.text.trim(),
      isPinned:
          widget.customer?.isPinned ?? false,
      balance:
          widget.customer?.balance ?? 0,
    );

    await widget.onSave(customer);

    if (!mounted) return;

    Navigator.pop(context);

    ScaffoldMessenger.of(context)
        .showSnackBar(
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
      backgroundColor: cardColor,
      title: Text(
        widget.title,
        style: const TextStyle(
          fontWeight: FontWeight.bold,
        ),
      ),
      content:
          SingleChildScrollView(
        child: Column(
          mainAxisSize:
              MainAxisSize.min,
          children: [
            TextField(
              controller:
                  nameController,
              textInputAction:
                  TextInputAction.next,
              decoration:
                  const InputDecoration(
                labelText: 'نام *',
                prefixIcon:
                    Icon(Icons.person_outline),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller:
                  phoneController,
              keyboardType:
                  TextInputType.phone,
              textInputAction:
                  TextInputAction.next,
              decoration:
                  const InputDecoration(
                labelText: 'شماره تماس',
                prefixIcon:
                    Icon(Icons.phone_outlined),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller:
                  cityController,
              textInputAction:
                  TextInputAction.done,
              decoration:
                  const InputDecoration(
                labelText: 'شهر / محل',
                prefixIcon: Icon(
                  Icons.location_on_outlined,
                ),
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
          onPressed:
              saving ? null : _save,
          style:
              FilledButton.styleFrom(
            backgroundColor:
                primaryBlue,
          ),
          child: saving
              ? const SizedBox(
                  width: 20,
                  height: 20,
                  child:
                      CircularProgressIndicator(
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

class CustomerDetailsPage
    extends StatefulWidget {
  final Customer customer;
  final TransactionStore transactionStore;

  const CustomerDetailsPage({
    super.key,
    required this.customer,
    required this.transactionStore,
  });

  @override
  State<CustomerDetailsPage> createState() =>
      _CustomerDetailsPageState();
}

class _CustomerDetailsPageState
    extends State<CustomerDetailsPage> {
  double _total(
    String accountType,
  ) {
    double total = 0;

    for (final currency in [
      'AFN',
      'TOMAN',
      'USD',
      'TRY',
    ]) {
      total +=
          widget.transactionStore
              .totalForCustomer(
        widget.customer.id,
        accountType: accountType,
        currency: currency,
      );
    }

    return total;
  }

  Future<void> _addTransaction() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) =>
            AddTransactionPage(
          customer: widget.customer,
          transactionStore:
              widget.transactionStore,
        ),
      ),
    );

    if (mounted) {
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    final customer = widget.customer;

    final transactions =
        widget.transactionStore
            .forCustomer(customer.id);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: const Text(
          'جزئیات حساب',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      body: ListView(
        padding:
            const EdgeInsets.all(16),
        children: [
          Container(
            padding:
                const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius:
                  BorderRadius.circular(20),
            ),
            child: Column(
              children: [
                CustomerAvatar(
                  customer: customer,
                ),
                const SizedBox(height: 12),
                Text(
                  customer.name,
                  style:
                      const TextStyle(
                    fontSize: 21,
                    fontWeight:
                        FontWeight.bold,
                  ),
                ),
                if (customer
                    .phone.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(customer.phone),
                ],
                if (customer
                    .city.isNotEmpty) ...[
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
                  amount:
                      _formatNumber(
                    _total('receivable'),
                  ),
                  color: Colors.green,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: BalanceCard(
                  title: 'بدهی',
                  amount:
                      _formatNumber(
                    _total('payable'),
                  ),
                  color: Colors.redAccent,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          SizedBox(
            height: 54,
            child:
                ElevatedButton.icon(
              onPressed:
                  _addTransaction,
              icon: const Icon(
                Icons.add_card,
              ),
              label: const Text(
                'ثبت تراکنش جدید',
                style: TextStyle(
                  fontWeight:
                      FontWeight.bold,
                ),
              ),
              style:
                  ElevatedButton.styleFrom(
                backgroundColor:
                    primaryBlue,
                foregroundColor:
                    Colors.white,
              ),
            ),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) =>
                      TransactionHistoryPage(
                    customer: customer,
                    transactionStore:
                        widget
                            .transactionStore,
                  ),
                ),
              );
            },
            icon:
                const Icon(Icons.history),
            label:
                const Text('تاریخچه حساب'),
          ),
          const SizedBox(height: 20),
          if (transactions.isEmpty)
            Container(
              padding:
                  const EdgeInsets.all(20),
              decoration:
                  BoxDecoration(
                color: cardColor,
                borderRadius:
                    BorderRadius.circular(
                  18,
                ),
              ),
              child: const Column(
                children: [
                  Icon(
                    Icons
                        .receipt_long_outlined,
                    size: 45,
                    color: Colors.grey,
                  ),
                  SizedBox(height: 10),
                  Text(
                    'هنوز تراکنشی ثبت نشده است.',
                  ),
                ],
              ),
            )
          else
            ...transactions.take(5).map(
                  (transaction) =>
                      TransactionCard(
                    transaction:
                        transaction,
                    onDelete: () async {
                      await widget
                          .transactionStore
                          .delete(
                        transaction,
                      );

                      if (mounted) {
                        setState(() {});
                      }
                    },
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

class BalanceCard
    extends StatelessWidget {
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
      padding:
          const EdgeInsets.symmetric(
        vertical: 16,
        horizontal: 8,
      ),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius:
            BorderRadius.circular(16),
        border: Border.all(
          color: color.withOpacity(0.25),
        ),
      ),
      child: Column(
        children: [
          Text(
            title,
            style: TextStyle(
              color: color,
              fontWeight:
                  FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            amount,
            textAlign:
                TextAlign.center,
            style: const TextStyle(
              fontSize: 17,
              fontWeight:
                  FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Add Transaction Page
// ============================================================

class AddTransactionPage
    extends StatefulWidget {
  final Customer customer;
  final TransactionStore transactionStore;

  const AddTransactionPage({
    super.key,
    required this.customer,
    required this.transactionStore,
  });

  @override
  State<AddTransactionPage>
      createState() =>
          _AddTransactionPageState();
}

class _AddTransactionPageState
    extends State<AddTransactionPage> {
  final TextEditingController
      amountController =
      TextEditingController();

  final TextEditingController
      descriptionController =
      TextEditingController();

  String accountType = 'receivable';
  String type = 'deposit';
  String currency = 'AFN';

  bool saving = false;

  @override
  void dispose() {
    amountController.dispose();
    descriptionController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final amount =
        double.tryParse(
      amountController.text
          .trim()
          .replaceAll(',', ''),
    );

    if (amount == null ||
        amount <= 0) {
      ScaffoldMessenger.of(context)
          .showSnackBar(
        const SnackBar(
          content:
              Text('مبلغ معتبر وارد کنید.'),
        ),
      );
      return;
    }

    setState(() {
      saving = true;
    });

    final transaction =
        AccountTransaction(
      id: DateTime.now()
          .microsecondsSinceEpoch
          .toString(),
      customerId:
          widget.customer.id,
      accountType: accountType,
      type: type,
      currency: currency,
      amount: amount,
      description:
          descriptionController.text
              .trim(),
      date: DateTime.now(),
    );

    await widget.transactionStore
        .add(transaction);

    if (!mounted) return;

    Navigator.pop(context);

    ScaffoldMessenger.of(context)
        .showSnackBar(
      const SnackBar(
        content:
            Text('تراکنش با موفقیت ثبت شد.'),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title:
            const Text('ثبت تراکنش جدید'),
      ),
      body: ListView(
        padding:
            const EdgeInsets.all(16),
        children: [
          Container(
            padding:
                const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius:
                  BorderRadius.circular(18),
            ),
            child: Row(
              children: [
                CustomerAvatar(
                  customer: widget.customer,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    widget.customer.name,
                    style:
                        const TextStyle(
                      fontSize: 18,
                      fontWeight:
                          FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<
              String>(
            value: accountType,
            decoration:
                const InputDecoration(
              labelText:
                  'نوع حساب',
            ),
            items: const [
              DropdownMenuItem(
                value: 'receivable',
                child: Text('طلب'),
              ),
              DropdownMenuItem(
                value: 'payable',
                child: Text('بدهی'),
              ),
            ],
            onChanged: (value) {
              if (value != null) {
                setState(() {
                  accountType =
                      value;
                });
              }
            },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<
              String>(
            value: type,
            decoration:
                const InputDecoration(
              labelText:
                  'نوع تراکنش',
            ),
            items: const [
              DropdownMenuItem(
                value: 'deposit',
                child:
                    Text('ثبت مبلغ'),
              ),
              DropdownMenuItem(
                value: 'withdrawal',
                child:
                    Text('برداشت / تسویه'),
              ),
            ],
            onChanged: (value) {
              if (value != null) {
                setState(() {
                  type = value;
                });
              }
            },
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<
              String>(
            value: currency,
            decoration:
                const InputDecoration(
              labelText: 'ارز',
            ),
            items: const [
              DropdownMenuItem(
                value: 'AFN',
                child:
                    Text('افغانی (AFN)'),
              ),
              DropdownMenuItem(
                value: 'TOMAN',
                child:
                    Text('تومان'),
              ),
              DropdownMenuItem(
                value: 'USD',
                child:
                    Text('دلار (USD)'),
              ),
              DropdownMenuItem(
                value: 'TRY',
                child:
                    Text('لیر ترکیه (TRY)'),
              ),
            ],
            onChanged: (value) {
              if (value != null) {
                setState(() {
                  currency = value;
                });
              }
            },
          ),
          const SizedBox(height: 12),
          TextField(
            controller:
                amountController,
            keyboardType:
                const TextInputType
                    .numberWithOptions(
              decimal: true,
            ),
            decoration:
                const InputDecoration(
              labelText: 'مبلغ *',
              prefixIcon:
                  Icon(Icons.numbers),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller:
                descriptionController,
            maxLines: 3,
            decoration:
                const InputDecoration(
              labelText: 'توضیحات',
              prefixIcon:
                  Icon(Icons.notes),
            ),
          ),
          const SizedBox(height: 24),
          SizedBox(
            height: 54,
            child:
                ElevatedButton.icon(
              onPressed:
                  saving ? null : _save,
              icon: const Icon(
                Icons.save_outlined,
              ),
              label:
                  const Text('ثبت تراکنش'),
              style:
                  ElevatedButton.styleFrom(
                backgroundColor:
                    primaryBlue,
                foregroundColor:
                    Colors.white,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Transaction Card
// ============================================================

class TransactionCard
    extends StatelessWidget {
  final AccountTransaction transaction;
  final VoidCallback onDelete;

  const TransactionCard({
    super.key,
    required this.transaction,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final isReceivable =
        transaction.accountType ==
            'receivable';

    final color = isReceivable
        ? Colors.green
        : Colors.redAccent;

    return Card(
      color: cardColor,
      margin:
          const EdgeInsets.only(bottom: 10),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor:
              color.withOpacity(0.15),
          child: Icon(
            isReceivable
                ? Icons
                    .arrow_downward_rounded
                : Icons
                    .arrow_upward_rounded,
            color: color,
          ),
        ),
        title: Text(
          '${_formatNumber(transaction.amount)} ${_currencyName(transaction.currency)}',
          style:
              const TextStyle(
            fontWeight:
                FontWeight.bold,
          ),
        ),
        subtitle: Text(
          '${isReceivable ? 'طلب' : 'بدهی'} • ${_formatDate(transaction.date)}'
          '${transaction.description.isEmpty ? '' : '\n${transaction.description}'}',
        ),
        trailing:
            PopupMenuButton<String>(
          onSelected: (value) {
            if (value == 'delete') {
              onDelete();
            }
          },
          itemBuilder: (_) => const [
            PopupMenuItem(
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
// Transaction History
// ============================================================

class TransactionHistoryPage
    extends StatelessWidget {
  final Customer customer;
  final TransactionStore transactionStore;

  const TransactionHistoryPage({
    super.key,
    required this.customer,
    required this.transactionStore,
  });

  @override
  Widget build(BuildContext context) {
    final transactions =
        transactionStore
            .forCustomer(customer.id);

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title:
            const Text('تاریخچه حساب'),
      ),
      body: transactions.isEmpty
          ? const Center(
              child: Text(
                'تراکنشی ثبت نشده است.',
              ),
            )
          : ListView.builder(
              padding:
                  const EdgeInsets.all(16),
              itemCount:
                  transactions.length,
              itemBuilder:
                  (context, index) {
                final transaction =
                    transactions[index];

                return TransactionCard(
                  transaction:
                      transaction,
                  onDelete: () async {
                    await transactionStore
                        .delete(
                      transaction,
                    );

                    if (context.mounted) {
                      Navigator.pop(
                        context,
                      );
                    }
                  },
                );
              },
            ),
    );
  }
}

// ============================================================
// Account Type Page
// ============================================================

class AccountTypePage
    extends StatelessWidget {
  final String title;
  final String accountType;
  final TransactionStore transactionStore;
  final CustomerStore customerStore;

  const AccountTypePage({
    super.key,
    required this.title,
    required this.accountType,
    required this.transactionStore,
    required this.customerStore,
  });

  Customer? findCustomer(
    String id,
  ) {
    for (final customer
        in customerStore.customers) {
      if (customer.id == id) {
        return customer;
      }
    }

    return null;
  }

  @override
  Widget build(BuildContext context) {
    final transactions =
        transactionStore.transactions
            .where(
              (item) =>
                  item.accountType ==
                  accountType,
            )
            .toList();

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title: Text(title),
      ),
      body: transactions.isEmpty
          ? const Center(
              child: Text(
                'موردی ثبت نشده است.',
              ),
            )
          : ListView.builder(
              padding:
                  const EdgeInsets.all(16),
              itemCount:
                  transactions.length,
              itemBuilder:
                  (context, index) {
                final transaction =
                    transactions[index];

                final customer =
                    findCustomer(
                  transaction.customerId,
                );

                return Card(
                  color: cardColor,
                  margin:
                      const EdgeInsets.only(
                    bottom: 10,
                  ),
                  child: ListTile(
                    leading:
                        const Icon(
                      Icons.person_outline,
                    ),
                    title: Text(
                      customer?.name ??
                          'حساب حذف شده',
                      style:
                          const TextStyle(
                        fontWeight:
                            FontWeight.bold,
                      ),
                    ),
                    subtitle: Text(
                      '${_formatNumber(transaction.amount)} ${_currencyName(transaction.currency)}'
                      ' • ${_formatDate(transaction.date)}',
                    ),
                  ),
                );
              },
            ),
    );
  }
}

// ============================================================
// All Transactions
// ============================================================

class AllTransactionsPage
    extends StatelessWidget {
  final TransactionStore transactionStore;
  final CustomerStore customerStore;

  const AllTransactionsPage({
    super.key,
    required this.transactionStore,
    required this.customerStore,
  });

  Customer? findCustomer(
    String id,
  ) {
    for (final customer
        in customerStore.customers) {
      if (customer.id == id) {
        return customer;
      }
    }

    return null;
  }

  @override
  Widget build(BuildContext context) {
    final transactions =
        transactionStore.transactions;

    return Scaffold(
      backgroundColor: backgroundColor,
      appBar: AppBar(
        title:
            const Text('همه تراکنش‌ها'),
      ),
      body: transactions.isEmpty
          ? const Center(
              child: Text(
                'هنوز تراکنشی ثبت نشده است.',
              ),
            )
          : ListView.builder(
              padding:
                  const EdgeInsets.all(16),
              itemCount:
                  transactions.length,
              itemBuilder:
                  (context, index) {
                final transaction =
                    transactions[index];

                final customer =
                    findCustomer(
                  transaction.customerId,
                );

                return Card(
                  color: cardColor,
                  margin:
                      const EdgeInsets.only(
                    bottom: 10,
                  ),
                  child: ListTile(
                    leading:
                        const Icon(
                      Icons.receipt_long,
                    ),
                    title: Text(
                      customer?.name ??
                          'حساب حذف شده',
                      style:
                          const TextStyle(
                        fontWeight:
                            FontWeight.bold,
                      ),
                    ),
                    subtitle: Text(
                      '${transaction.accountType == 'receivable' ? 'طلب' : 'بدهی'}'
                      ' • ${_formatNumber(transaction.amount)} ${_currencyName(transaction.currency)}'
                      ' • ${_formatDate(transaction.date)}',
                    ),
                  ),
                );
              },
            ),
    );
  }
}

// ============================================================
// Settings Page
// ============================================================

class SettingsPage
    extends StatefulWidget {
  const SettingsPage({
    super.key,
  });

  @override
  State<SettingsPage> createState() =>
      _SettingsPageState();
}

class _SettingsPageState
    extends State<SettingsPage> {
  bool fingerprintEnabled =
      false;

  DateTime? lastSyncAttempt;

  @override
  void initState() {
    super.initState();

    _loadSettings();
  }

  void _loadSettings() {
    setState(() {
      fingerprintEnabled =
          SettingsStorage
              .fingerprintEnabled;

      lastSyncAttempt =
          SettingsStorage
              .lastSyncAttempt;
    });
  }

  Future<void>
      _changeFingerprint(
    bool value,
  ) async {
    await SettingsStorage
        .setFingerprintEnabled(
      value,
    );

    if (!mounted) return;

    setState(() {
      fingerprintEnabled =
          value;
    });

    ScaffoldMessenger.of(context)
        .showSnackBar(
      SnackBar(
        content: Text(
          value
              ? 'گزینه اثر انگشت فعال شد.'
              : 'گزینه اثر انگشت غیرفعال شد.',
        ),
      ),
    );
  }

  Future<void>
      _syncInformation() async {
    final now = DateTime.now();

    await SettingsStorage
        .setLastSyncAttempt(now);

    if (!mounted) return;

    setState(() {
      lastSyncAttempt = now;
    });

    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: cardColor,
        title:
            const Text('همگام‌سازی'),
        content: const Text(
          'اطلاعات محلی برنامه ثبت شد.\n\n'
          'اتصال واقعی به سرور و همگام‌سازی آنلاین '
          'در مرحله اتصال API انجام خواهد شد.',
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context);
            },
            child:
                const Text('باشه'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor:
          backgroundColor,
      appBar: AppBar(
        title:
            const Text('تنظیمات'),
      ),
      body: ListView(
        padding:
            const EdgeInsets.all(16),
        children: [
          Container(
            padding:
                const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius:
                  BorderRadius.circular(18),
            ),
            child: const Row(
              children: [
                Icon(
                  Icons.settings,
                  color: primaryBlue,
                  size: 30,
                ),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'تنظیمات برنامه',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight:
                          FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          SettingsItem(
            icon:
                Icons.lock_outline,
            title:
                'امنیت و رمز ورود',
            subtitle:
                'تنظیم یا تغییر رمز برنامه',
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) =>
                      const SecurityPage(),
                ),
              );
            },
          ),
          Card(
            color: cardColor,
            margin:
                const EdgeInsets.only(
              bottom: 10,
            ),
            child: SwitchListTile(
              secondary:
                  const Icon(
                Icons.fingerprint,
                color: primaryBlue,
              ),
              title: const Text(
                'اثر انگشت',
                style: TextStyle(
                  fontWeight:
                      FontWeight.w600,
                ),
              ),
              subtitle: Text(
                fingerprintEnabled
                    ? 'فعال'
                    : 'غیرفعال',
              ),
              value:
                  fingerprintEnabled,
              onChanged:
                  _changeFingerprint,
            ),
          ),
          SettingsItem(
            icon: Icons.sync,
            title:
                'همگام‌سازی اطلاعات',
            subtitle:
                lastSyncAttempt == null
                    ? 'هنوز انجام نشده'
                    : 'آخرین تلاش: ${_formatDateTime(lastSyncAttempt!)}',
            onTap:
                _syncInformation,
          ),
          SettingsItem(
            icon:
                Icons.info_outline,
            title:
                'درباره برنامه',
            subtitle:
                'نسخه 1.0.0',
            onTap: () {
              showAboutDialog(
                context: context,
                applicationName:
                    'مدیریت حساب‌ها',
                applicationVersion:
                    '1.0.0',
                applicationLegalese:
                    '© مدیریت حساب‌ها',
              );
            },
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Security Page
// ============================================================

class SecurityPage
    extends StatefulWidget {
  const SecurityPage({
    super.key,
  });

  @override
  State<SecurityPage> createState() =>
      _SecurityPageState();
}

class _SecurityPageState
    extends State<SecurityPage> {
  final TextEditingController
      currentPinController =
      TextEditingController();

  final TextEditingController
      newPinController =
      TextEditingController();

  final TextEditingController
      confirmPinController =
      TextEditingController();

  bool saving = false;

  @override
  void dispose() {
    currentPinController.dispose();
    newPinController.dispose();
    confirmPinController.dispose();

    super.dispose();
  }

  Future<void> _savePin() async {
    final oldPin =
        SettingsStorage.appPin;

    final current =
        currentPinController.text
            .trim();

    final newPin =
        newPinController.text.trim();

    final confirm =
        confirmPinController.text
            .trim();

    if (oldPin.isNotEmpty &&
        current != oldPin) {
      ScaffoldMessenger.of(context)
          .showSnackBar(
        const SnackBar(
          content:
              Text('رمز فعلی اشتباه است.'),
        ),
      );
      return;
    }

    if (!RegExp(
      r'^\d{4}$',
    ).hasMatch(newPin)) {
      ScaffoldMessenger.of(context)
          .showSnackBar(
        const SnackBar(
          content:
              Text('رمز جدید باید ۴ رقم باشد.'),
        ),
      );
      return;
    }

    if (newPin != confirm) {
      ScaffoldMessenger.of(context)
          .showSnackBar(
        const SnackBar(
          content:
              Text('تکرار رمز با رمز جدید یکسان نیست.'),
        ),
      );
      return;
    }

    setState(() {
      saving = true;
    });

    await SettingsStorage.setAppPin(
      newPin,
    );

    if (!mounted) return;

    setState(() {
      saving = false;
    });

    currentPinController.clear();
    newPinController.clear();
    confirmPinController.clear();

    ScaffoldMessenger.of(context)
        .showSnackBar(
      SnackBar(
        content: Text(
          oldPin.isEmpty
              ? 'رمز برنامه با موفقیت تنظیم شد.'
              : 'رمز برنامه با موفقیت تغییر کرد.',
        ),
      ),
    );
  }

  Future<void> _removePin() async {
    final oldPin =
        SettingsStorage.appPin;

    if (oldPin.isEmpty) {
      ScaffoldMessenger.of(context)
          .showSnackBar(
        const SnackBar(
          content:
              Text('هنوز رمزی تنظیم نشده است.'),
        ),
      );
      return;
    }

    final controller =
        TextEditingController();

    final confirmed =
        await showDialog<bool>(
      context: context,
      builder: (_) {
        return AlertDialog(
          backgroundColor: cardColor,
          title:
              const Text('حذف رمز'),
          content: TextField(
            controller: controller,
            keyboardType:
                TextInputType.number,
            obscureText: true,
            maxLength: 4,
            decoration:
                const InputDecoration(
              labelText:
                  'رمز فعلی',
            ),
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(
                  context,
                  false,
                );
              },
              child:
                  const Text('لغو'),
            ),
            FilledButton(
              onPressed: () {
                Navigator.pop(
                  context,
                  controller.text
                          .trim() ==
                      oldPin,
                );
              },
              child:
                  const Text('حذف'),
            ),
          ],
        );
      },
    );

    controller.dispose();

    if (confirmed != true) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(
          const SnackBar(
            content:
                Text('رمز واردشده صحیح نیست.'),
          ),
        );
      }
      return;
    }

    await SettingsStorage.setAppPin('');

    if (mounted) {
      setState(() {});

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(
        const SnackBar(
          content:
              Text('رمز برنامه حذف شد.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasPin =
        SettingsStorage.appPin
            .isNotEmpty;

    return Scaffold(
      backgroundColor:
          backgroundColor,
      appBar: AppBar(
        title:
            const Text('امنیت و رمز ورود'),
      ),
      body: ListView(
        padding:
            const EdgeInsets.all(16),
        children: [
          Container(
            padding:
                const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: cardColor,
              borderRadius:
                  BorderRadius.circular(18),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.lock,
                  color: primaryBlue,
                  size: 32,
                ),
                const SizedBox(
                  width: 12,
                ),
                Expanded(
                  child: Text(
                    hasPin
                        ? 'رمز برنامه فعال است'
                        : 'برای برنامه رمز تعیین نشده است',
                    style:
                        const TextStyle(
                      fontWeight:
                          FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          if (hasPin)
            TextField(
              controller:
                  currentPinController,
              keyboardType:
                  TextInputType.number,
              obscureText: true,
              maxLength: 4,
              decoration:
                  const InputDecoration(
                labelText:
                    'رمز فعلی',
                prefixIcon:
                    Icon(Icons.lock_outline),
              ),
            ),
          if (hasPin)
            const SizedBox(height: 12),
          TextField(
            controller:
                newPinController,
            keyboardType:
                TextInputType.number,
            obscureText: true,
            maxLength: 4,
            decoration:
                const InputDecoration(
              labelText:
                  'رمز جدید ۴ رقمی',
              prefixIcon:
                  Icon(Icons.password),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller:
                confirmPinController,
            keyboardType:
                TextInputType.number,
            obscureText: true,
            maxLength: 4,
            decoration:
                const InputDecoration(
              labelText:
                  'تکرار رمز جدید',
              prefixIcon:
                  Icon(Icons.password_outlined),
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            height: 52,
            child:
                ElevatedButton.icon(
              onPressed:
                  saving ? null : _savePin,
              icon: const Icon(
                Icons.save,
              ),
              label:
                  const Text('ذخیره رمز'),
              style:
                  ElevatedButton.styleFrom(
                backgroundColor:
                    primaryBlue,
                foregroundColor:
                    Colors.white,
              ),
            ),
          ),
          if (hasPin) ...[
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed:
                  _removePin,
              icon: const Icon(
                Icons.lock_open,
              ),
              label:
                  const Text('حذف رمز برنامه'),
            ),
          ],
          const SizedBox(height: 20),
          Container(
            padding:
                const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.orange
                  .withOpacity(0.10),
              borderRadius:
                  BorderRadius.circular(14),
            ),
            child: const Text(
              'توجه: این رمز فعلاً برای قفل محلی برنامه استفاده می‌شود. اتصال کامل آن به سیستم احراز هویت سرور در مرحله اتصال API انجام خواهد شد.',
              style:
                  TextStyle(height: 1.6),
            ),
          ),
        ],
      ),
    );
  }
}

// ============================================================
// Settings Item
// ============================================================

class SettingsItem
    extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback onTap;

  const SettingsItem({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      color: cardColor,
      margin:
          const EdgeInsets.only(
        bottom: 10,
      ),
      child: ListTile(
        onTap: onTap,
        leading: Icon(
          icon,
          color: primaryBlue,
        ),
        title: Text(
          title,
          style:
              const TextStyle(
            fontWeight:
                FontWeight.w600,
          ),
        ),
        subtitle: subtitle == null
            ? null
            : Text(subtitle!),
        trailing:
            const Icon(
          Icons.chevron_left,
        ),
      ),
    );
  }
}

// ============================================================
// Helpers
// ============================================================

String _formatNumber(
  double number,
) {
  if (number == number.roundToDouble()) {
    return number
        .toInt()
        .toString();
  }

  return number
      .toStringAsFixed(2);
}

String _currencyName(
  String currency,
) {
  switch (currency) {
    case 'AFN':
      return 'افغانی';

    case 'TOMAN':
      return 'تومان';

    case 'USD':
      return 'دلار';

    case 'TRY':
      return 'لیر';

    default:
      return currency;
  }
}

String _formatDate(
  DateTime date,
) {
  final y = date.year
      .toString()
      .padLeft(4, '0');

  final m = date.month
      .toString()
      .padLeft(2, '0');

  final d = date.day
      .toString()
      .padLeft(2, '0');

  return '$y/$m/$d';
}

String _formatDateTime(
  DateTime date,
) {
  final y = date.year
      .toString()
      .padLeft(4, '0');

  final m = date.month
      .toString()
      .padLeft(2, '0');

  final d = date.day
      .toString()
      .padLeft(2, '0');

  final h = date.hour
      .toString()
      .padLeft(2, '0');

  final min = date.minute
      .toString()
      .padLeft(2, '0');

  return '$y/$m/$d - $h:$min';
}
