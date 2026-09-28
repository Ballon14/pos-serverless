import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/constants.dart';

class FirebaseService {
  static final FirebaseService _instance = FirebaseService._internal();
  factory FirebaseService() => _instance;
  FirebaseService._internal();

  final String _baseUrl = AppConstants.firebaseRtdbUrl;

  Uri _buildUri(String path) {
    var cleanPath = path.trim();
    if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);
    if (!cleanPath.endsWith('.json')) cleanPath = '$cleanPath.json';
    return Uri.parse('$_baseUrl/$cleanPath');
  }

  /// Perform a GET request to Firebase Realtime Database
  Future<dynamic> get(String path) async {
    final response = await http.get(_buildUri(path));
    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (response.body.isEmpty || response.body == 'null') return null;
      return jsonDecode(response.body);
    }
    throw Exception('Firebase GET error [${response.statusCode}]: ${response.body}');
  }

  /// Perform a PUT request (replaces or creates node at path)
  Future<dynamic> put(String path, dynamic data) async {
    final response = await http.put(
      _buildUri(path),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(data),
    );
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return jsonDecode(response.body);
    }
    throw Exception('Firebase PUT error [${response.statusCode}]: ${response.body}');
  }

  /// Perform a POST request (appends new child with unique push ID)
  Future<String> post(String path, dynamic data) async {
    final response = await http.post(
      _buildUri(path),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(data),
    );
    if (response.statusCode >= 200 && response.statusCode < 300) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      return decoded['name'] as String; // Firebase push ID
    }
    throw Exception('Firebase POST error [${response.statusCode}]: ${response.body}');
  }

  /// Perform a PATCH request (updates specific child keys)
  Future<dynamic> patch(String path, Map<String, dynamic> data) async {
    final response = await http.patch(
      _buildUri(path),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(data),
    );
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return jsonDecode(response.body);
    }
    throw Exception('Firebase PATCH error [${response.statusCode}]: ${response.body}');
  }

  /// Perform a DELETE request
  Future<void> delete(String path) async {
    final response = await http.delete(_buildUri(path));
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return;
    }
    throw Exception('Firebase DELETE error [${response.statusCode}]: ${response.body}');
  }

  /// Seed initial database data if categories or products are empty
  Future<void> seedInitialDataIfEmpty() async {
    try {
      final existingCategories = await get('categories');
      if (existingCategories != null) {
        return; // Data already exists
      }

      final now = DateTime.now().toIso8601String();

      // 1. Seed Users
      final usersData = {
        '00000000-0000-0000-0000-000000000001': {
          'id': '00000000-0000-0000-0000-000000000001',
          'name': 'Administrator',
          'email': 'admin@tokombaemi.com',
          'password': 'password123',
          'role': 'admin',
          'is_active': true,
          'created_at': now,
          'updated_at': now,
        },
        '00000000-0000-0000-0000-000000000002': {
          'id': '00000000-0000-0000-0000-000000000002',
          'name': 'Siti Kasir',
          'email': 'kasir1@tokombaemi.com',
          'password': 'password123',
          'role': 'kasir',
          'is_active': true,
          'created_at': now,
          'updated_at': now,
        },
        '00000000-0000-0000-0000-000000000003': {
          'id': '00000000-0000-0000-0000-000000000003',
          'name': 'Budi Kasir',
          'email': 'kasir2@tokombaemi.com',
          'password': 'password123',
          'role': 'kasir',
          'is_active': true,
          'created_at': now,
          'updated_at': now,
        },
      };
      await put('users', usersData);

      // 2. Seed Categories
      final categoriesData = {
        '10000000-0000-0000-0000-000000000001': {
          'id': '10000000-0000-0000-0000-000000000001',
          'name': 'Makanan',
          'slug': 'makanan',
          'description': 'Produk makanan ringan & berat',
          'is_active': true,
          'created_at': now,
        },
        '10000000-0000-0000-0000-000000000002': {
          'id': '10000000-0000-0000-0000-000000000002',
          'name': 'Minuman',
          'slug': 'minuman',
          'description': 'Minuman kemasan & segar',
          'is_active': true,
          'created_at': now,
        },
        '10000000-0000-0000-0000-000000000003': {
          'id': '10000000-0000-0000-0000-000000000003',
          'name': 'Kebersihan',
          'slug': 'kebersihan',
          'description': 'Produk kebersihan rumah tangga',
          'is_active': true,
          'created_at': now,
        },
        '10000000-0000-0000-0000-000000000004': {
          'id': '10000000-0000-0000-0000-000000000004',
          'name': 'Sembako',
          'slug': 'sembako',
          'description': 'Kebutuhan pokok sehari-hari',
          'is_active': true,
          'created_at': now,
        },
        '10000000-0000-0000-0000-000000000005': {
          'id': '10000000-0000-0000-0000-000000000005',
          'name': 'Alat Tulis',
          'slug': 'alat-tulis',
          'description': 'Peralatan tulis dan kantor',
          'is_active': true,
          'created_at': now,
        },
      };
      await put('categories', categoriesData);

      // 3. Seed Suppliers
      final suppliersData = {
        '20000000-0000-0000-0000-000000000001': {
          'id': '20000000-0000-0000-0000-000000000001',
          'name': 'PT Indofood Sukses Makmur',
          'code': 'SUP-001',
          'phone': '021-5795-8822',
          'email': 'supplier@indofood.com',
          'address': 'Jakarta',
          'contact_person': 'Bpk. Salim',
          'is_active': true,
          'created_at': now,
        },
        '20000000-0000-0000-0000-000000000002': {
          'id': '20000000-0000-0000-0000-000000000002',
          'name': 'PT Wings Surya',
          'code': 'SUP-002',
          'phone': '031-8431-234',
          'email': 'supplier@wings.com',
          'address': 'Surabaya',
          'contact_person': 'Ibu Rahma',
          'is_active': true,
          'created_at': now,
        },
      };
      await put('suppliers', suppliersData);

      // 4. Seed Products
      final productsData = {
        '30000000-0000-0000-0000-000000000001': {
          'id': '30000000-0000-0000-0000-000000000001',
          'category_id': '10000000-0000-0000-0000-000000000001',
          'name': 'Indomie Goreng',
          'sku': 'MKN-001',
          'harga_beli': 2500,
          'harga_jual': 3500,
          'grosir_tiers': [{'minQty': 40, 'harga': 3200}],
          'stok': 100,
          'min_stok': 20,
          'satuan': 'pcs',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000002': {
          'id': '30000000-0000-0000-0000-000000000002',
          'category_id': '10000000-0000-0000-0000-000000000001',
          'name': 'Chitato Original 68g',
          'sku': 'MKN-002',
          'harga_beli': 8000,
          'harga_jual': 11000,
          'grosir_tiers': [{'minQty': 10, 'harga': 10000}],
          'stok': 50,
          'min_stok': 10,
          'satuan': 'pcs',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000003': {
          'id': '30000000-0000-0000-0000-000000000003',
          'category_id': '10000000-0000-0000-0000-000000000001',
          'name': 'Roti Sari Roti Tawar',
          'sku': 'MKN-003',
          'harga_beli': 12000,
          'harga_jual': 15000,
          'grosir_tiers': [],
          'stok': 30,
          'min_stok': 5,
          'satuan': 'pcs',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000004': {
          'id': '30000000-0000-0000-0000-000000000004',
          'category_id': '10000000-0000-0000-0000-000000000002',
          'name': 'Aqua Botol 600ml',
          'sku': 'MNM-001',
          'harga_beli': 2500,
          'harga_jual': 4000,
          'grosir_tiers': [{'minQty': 24, 'harga': 3500}],
          'stok': 150,
          'min_stok': 24,
          'satuan': 'botol',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000005': {
          'id': '30000000-0000-0000-0000-000000000005',
          'category_id': '10000000-0000-0000-0000-000000000002',
          'name': 'Teh Pucuk Harum 350ml',
          'sku': 'MNM-002',
          'harga_beli': 2800,
          'harga_jual': 4000,
          'grosir_tiers': [{'minQty': 24, 'harga': 3300}],
          'stok': 80,
          'min_stok': 12,
          'satuan': 'botol',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000006': {
          'id': '30000000-0000-0000-0000-000000000006',
          'category_id': '10000000-0000-0000-0000-000000000002',
          'name': 'Ultra Milk Coklat 250ml',
          'sku': 'MNM-003',
          'harga_beli': 5000,
          'harga_jual': 6500,
          'grosir_tiers': [{'minQty': 24, 'harga': 6000}],
          'stok': 40,
          'min_stok': 10,
          'satuan': 'kotak',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000007': {
          'id': '30000000-0000-0000-0000-000000000007',
          'category_id': '10000000-0000-0000-0000-000000000004',
          'name': 'Beras Ramos Super 5kg',
          'sku': 'SBK-001',
          'harga_beli': 65000,
          'harga_jual': 75000,
          'grosir_tiers': [{'minQty': 5, 'harga': 72000}],
          'stok': 25,
          'min_stok': 5,
          'satuan': 'karung',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000008': {
          'id': '30000000-0000-0000-0000-000000000008',
          'category_id': '10000000-0000-0000-0000-000000000004',
          'name': 'Minyak Goreng Bimoli 2L',
          'sku': 'SBK-002',
          'harga_beli': 32000,
          'harga_jual': 38000,
          'grosir_tiers': [{'minQty': 6, 'harga': 36000}],
          'stok': 4, // Intentionally low stock for alert testing!
          'min_stok': 10,
          'satuan': 'pouch',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000009': {
          'id': '30000000-0000-0000-0000-000000000009',
          'category_id': '10000000-0000-0000-0000-000000000004',
          'name': 'Gula Pasir Gulaku 1kg',
          'sku': 'SBK-003',
          'harga_beli': 15000,
          'harga_jual': 18500,
          'grosir_tiers': [{'minQty': 10, 'harga': 17500}],
          'stok': 60,
          'min_stok': 15,
          'satuan': 'kg',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000010': {
          'id': '30000000-0000-0000-0000-000000000010',
          'category_id': '10000000-0000-0000-0000-000000000003',
          'name': 'Sunlight Jeruk Nipis 755ml',
          'sku': 'KBS-001',
          'harga_beli': 13500,
          'harga_jual': 17000,
          'grosir_tiers': [{'minQty': 12, 'harga': 16000}],
          'stok': 40,
          'min_stok': 10,
          'satuan': 'pouch',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000011': {
          'id': '30000000-0000-0000-0000-000000000011',
          'category_id': '10000000-0000-0000-0000-000000000003',
          'name': 'Rinso Anti Noda 770g',
          'sku': 'KBS-002',
          'harga_beli': 18000,
          'harga_jual': 22500,
          'grosir_tiers': [],
          'stok': 35,
          'min_stok': 8,
          'satuan': 'pack',
          'is_active': true,
          'created_at': now,
        },
        '30000000-0000-0000-0000-000000000012': {
          'id': '30000000-0000-0000-0000-000000000012',
          'category_id': '10000000-0000-0000-0000-000000000005',
          'name': 'Buku Tulis Sinar Dunia 38 Lembar',
          'sku': 'ALT-001',
          'harga_beli': 3000,
          'harga_jual': 4500,
          'grosir_tiers': [{'minQty': 10, 'harga': 4000}],
          'stok': 120,
          'min_stok': 20,
          'satuan': 'buku',
          'is_active': true,
          'created_at': now,
        },
      };
      await put('products', productsData);

      // 5. Seed Settings
      final settingsData = {
        'store_name': 'Toko Mba Emi',
        'store_address': 'Jl. Raya Utama No. 45, Jakarta',
        'store_phone': '0812-3456-7890',
        'receipt_footer': 'Terima kasih atas kunjungan Anda di Toko Mba Emi!',
      };
      await put('settings', settingsData);
    } catch (_) {
      // Ignore background seeding errors
    }
  }
}
