class AppValidators {
  AppValidators._();

  static String? requiredField(String? value, [String message = 'Field ini wajib diisi']) {
    if (value == null || value.trim().isEmpty) {
      return message;
    }
    return null;
  }

  static String? email(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Email wajib diisi';
    }
    final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    if (!emailRegex.hasMatch(value.trim())) {
      return 'Format email tidak valid';
    }
    return null;
  }

  static String? minLength(String? value, int min, [String? customMessage]) {
    if (value == null || value.length < min) {
      return customMessage ?? 'Minimal $min karakter';
    }
    return null;
  }

  static String? positiveNumber(String? value, [String message = 'Harus berupa angka positif']) {
    if (value == null || value.trim().isEmpty) return null;
    final num? parsed = num.tryParse(value);
    if (parsed == null || parsed < 0) {
      return message;
    }
    return null;
  }

  static String? positiveInteger(String? value, [String message = 'Harus berupa bilangan bulat positif']) {
    if (value == null || value.trim().isEmpty) return null;
    final int? parsed = int.tryParse(value);
    if (parsed == null || parsed <= 0) {
      return message;
    }
    return null;
  }
}
