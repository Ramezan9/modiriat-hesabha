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

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'مدیریت حساب‌ها',
          style: TextStyle(
            fontWeight: FontWeight.bold,
          ),
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
              _buildAnnouncementCard(),

              const SizedBox(height: 16),

              Row(
                children: [
                  Expanded(
                    child: _DashboardCard(
                      title: 'مدیریت کل حساب‌ها',
                      subtitle: 'همه مشتریان و حساب‌ها',
                      icon: Icons.account_balance_wallet_outlined,
                      iconColor: const Color(0xFF1565C0),
                      onTap: () {},
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _DashboardCard(
                      title: 'برداشت‌های خودم',
                      subtitle: 'برداشت‌ها و تسویه‌ها',
                      icon: Icons.arrow_upward_rounded,
                      iconColor: Colors.red,
                      onTap: () {},
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 12),

              Row(
                children: [
                  Expanded(
                    child: _DashboardCard(
                      title: 'طلب‌ها',
                      subtitle: 'مبالغی که باید دریافت شود',
                      icon: Icons.trending_up_rounded,
                      iconColor: Colors.green,
                      onTap: () {},
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _DashboardCard(
                      title: 'بدهی‌ها',
                      subtitle: 'مبالغی که باید پرداخت شود',
                      icon: Icons.trending_down_rounded,
                      iconColor: Colors.red,
                      onTap: () {},
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 20),

              _buildSectionTitle(
                title: 'خلاصه موجودی',
                icon: Icons.account_balance_outlined,
              ),

              const SizedBox(height: 10),

              _buildCurrencyCard(
                title: 'افغانی',
                code: 'AFN',
                amount: '0',
                icon: '؋',
              ),

              _buildCurrencyCard(
                title: 'تومان',
                code: 'TOMAN',
                amount: '0',
                icon: 'ت',
              ),

              _buildCurrencyCard(
                title: 'دلار',
                code: 'USD',
                amount: '0',
                icon: '\$',
              ),

              _buildCurrencyCard(
                title: 'لیر ترکیه',
                code: 'TRY',
                amount: '0',
                icon: '₺',
              ),

              const SizedBox(height: 20),

              _buildSectionTitle(
                title: 'وضعیت حساب‌ها',
                icon: Icons.people_outline,
              ),

              const SizedBox(height: 10),

              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: const Row(
                  children: [
                    Expanded(
                      child: _CountItem(
                        title: 'مشتریان',
                        value: '0',
                        icon: Icons.people_alt_outlined,
                      ),
                    ),
                    Expanded(
                      child: _CountItem(
                        title: 'طلب',
                        value: '0',
                        icon: Icons.arrow_downward_rounded,
                      ),
                    ),
                    Expanded(
                      child: _CountItem(
                        title: 'بدهی',
                        value: '0',
                        icon: Icons.arrow_upward_rounded,
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
                  onPressed: () {},
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

  Widget _buildAnnouncementCard() {
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

  Widget _buildSectionTitle({
    required String title,
    required IconData icon,
  }) {
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

  Widget _buildCurrencyCard({
    required String title,
    required String code,
    required String amount,
    required String icon,
  }) {
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
}

class _DashboardCard extends StatelessWidget {
  const _DashboardCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.iconColor,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color iconColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        constraints: const BoxConstraints(
          minHeight: 150,
        ),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: iconColor.withValues(alpha: 0.10),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(
                icon,
                color: iconColor,
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
}

class _CountItem extends StatelessWidget {
  const _CountItem({
    required this.title,
    required this.value,
    required this.icon,
  });

  final String title;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
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
