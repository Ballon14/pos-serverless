import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/theme.dart';
import '../../models/category_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/product_provider.dart';

class CategoryScreen extends ConsumerStatefulWidget {
  const CategoryScreen({super.key});

  @override
  ConsumerState<CategoryScreen> createState() => _CategoryScreenState();
}

class _CategoryScreenState extends ConsumerState<CategoryScreen> {
  final _searchController = TextEditingController();
  String _search = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _openCategoryDialog([CategoryModel? existing]) {
    final nameCtrl = TextEditingController(text: existing?.name ?? '');
    final slugCtrl = TextEditingController(text: existing?.slug ?? '');
    final descCtrl = TextEditingController(text: existing?.description ?? '');

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(existing == null ? 'Tambah Kategori' : 'Edit Kategori'),
        content: SizedBox(
          width: 420,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameCtrl,
                decoration: const InputDecoration(
                  labelText: 'Nama Kategori *',
                  hintText: 'Misal: Minuman Kemasan',
                ),
                onChanged: (val) {
                  if (existing == null) {
                    slugCtrl.text = val.trim().toLowerCase().replaceAll(RegExp(r'\s+'), '-');
                  }
                },
              ),
              const SizedBox(height: 12),
              TextField(
                controller: slugCtrl,
                decoration: const InputDecoration(
                  labelText: 'Slug *',
                  hintText: 'minuman-kemasan',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: descCtrl,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Deskripsi (Opsional)',
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () async {
              if (nameCtrl.text.trim().isEmpty) return;

              final service = ref.read(categoryServiceProvider);
              final slug = slugCtrl.text.trim().isEmpty
                  ? nameCtrl.text.trim().toLowerCase().replaceAll(RegExp(r'\s+'), '-')
                  : slugCtrl.text.trim();

              try {
                if (existing == null) {
                  await service.createCategory(
                    name: nameCtrl.text.trim(),
                    slug: slug,
                    description: descCtrl.text.trim().isEmpty ? null : descCtrl.text.trim(),
                  );
                } else {
                  await service.updateCategory(
                    id: existing.id,
                    name: nameCtrl.text.trim(),
                    slug: slug,
                    description: descCtrl.text.trim().isEmpty ? null : descCtrl.text.trim(),
                  );
                }
                if (ctx.mounted) Navigator.pop(ctx);
                ref.invalidate(categoriesProvider);
              } catch (e) {
                if (ctx.mounted) {
                  ScaffoldMessenger.of(ctx).showSnackBar(SnackBar(content: Text('Error: $e')));
                }
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final categoriesAsync = ref.watch(categoriesProvider);
    final user = ref.watch(authProvider).value;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Master Kategori', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                    SizedBox(height: 4),
                    Text('Kelompokkan produk berdasarkan jenis atau varian', style: TextStyle(color: Colors.grey, fontSize: 13)),
                  ],
                ),
                if (user?.isAdmin == true)
                  ElevatedButton.icon(
                    onPressed: () => _openCategoryDialog(),
                    icon: const Icon(Icons.add_rounded, size: 18),
                    label: const Text('Tambah Kategori'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                    ),
                  ),
              ],
            ),

            const SizedBox(height: 20),

            // Search Bar
            TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Cari kategori...',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: _search.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded),
                        onPressed: () {
                          _searchController.clear();
                          setState(() => _search = '');
                        },
                      )
                    : null,
                filled: true,
                fillColor: isDark ? AppColors.darkSurface : AppColors.lightSurface,
              ),
              onChanged: (val) => setState(() => _search = val.trim().toLowerCase()),
            ),

            const SizedBox(height: 20),

            // Table of Categories
            Expanded(
              child: categoriesAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (e, _) => Center(child: Text('Gagal memuat kategori: $e')),
                data: (categories) {
                  final filtered = categories.where((c) {
                    if (_search.isEmpty) return true;
                    return c.name.toLowerCase().contains(_search) || c.slug.toLowerCase().contains(_search);
                  }).toList();

                  if (filtered.isEmpty) {
                    return Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.category_outlined, size: 64, color: Colors.grey.shade400),
                          const SizedBox(height: 12),
                          const Text('Belum ada kategori', style: TextStyle(color: Colors.grey, fontSize: 16)),
                        ],
                      ),
                    );
                  }

                  return Card(
                    clipBehavior: Clip.antiAlias,
                    child: ListView.separated(
                      itemCount: filtered.length,
                      separatorBuilder: (context, _) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final cat = filtered[index];
                        return ListTile(
                          leading: CircleAvatar(
                            backgroundColor: cat.isActive
                                ? AppColors.primary.withValues(alpha: 0.15)
                                : Colors.grey.withValues(alpha: 0.15),
                            child: Icon(
                              Icons.folder_open_rounded,
                              color: cat.isActive ? AppColors.primary : Colors.grey,
                            ),
                          ),
                          title: Row(
                            children: [
                              Text(cat.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: (cat.isActive ? AppColors.success : Colors.grey).withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  cat.isActive ? 'Aktif' : 'Nonaktif',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: cat.isActive ? AppColors.success : Colors.grey,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          subtitle: Text('Slug: ${cat.slug}${cat.description != null ? ' • ${cat.description}' : ''}'),
                          trailing: user?.isAdmin == true
                              ? Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    IconButton(
                                      icon: Icon(
                                        cat.isActive ? Icons.toggle_on_rounded : Icons.toggle_off_rounded,
                                        color: cat.isActive ? AppColors.success : Colors.grey,
                                        size: 28,
                                      ),
                                      tooltip: cat.isActive ? 'Nonaktifkan' : 'Aktifkan',
                                      onPressed: () async {
                                        await ref.read(categoryServiceProvider).toggleActive(cat.id);
                                        ref.invalidate(categoriesProvider);
                                      },
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.edit_outlined, size: 20),
                                      onPressed: () => _openCategoryDialog(cat),
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.delete_outline, size: 20, color: AppColors.error),
                                      onPressed: () async {
                                        final confirm = await showDialog<bool>(
                                          context: context,
                                          builder: (ctx) => AlertDialog(
                                            title: const Text('Hapus Kategori?'),
                                            content: Text('Yakin ingin menghapus kategori "${cat.name}"?'),
                                            actions: [
                                              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Batal')),
                                              ElevatedButton(
                                                style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
                                                onPressed: () => Navigator.pop(ctx, true),
                                                child: const Text('Hapus', style: TextStyle(color: Colors.white)),
                                              ),
                                            ],
                                          ),
                                        );
                                        if (confirm == true) {
                                          await ref.read(categoryServiceProvider).deleteCategory(cat.id);
                                          ref.invalidate(categoriesProvider);
                                        }
                                      },
                                    ),
                                  ],
                                )
                              : null,
                        );
                      },
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
