import 'package:intl/intl.dart';

class AppFormatters {
  AppFormatters._();

  static final NumberFormat _currencyFormat = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp ',
    decimalDigits: 0,
  );

  static final NumberFormat _numberFormat = NumberFormat.decimalPattern('id_ID');

  static final DateFormat _dateFormat = DateFormat('dd MMM yyyy', 'id_ID');
  static final DateFormat _dateTimeFormat = DateFormat('dd MMM yyyy, HH:mm', 'id_ID');
  static final DateFormat _timeFormat = DateFormat('HH:mm', 'id_ID');
  static final DateFormat _apiDateFormat = DateFormat('yyyy-MM-dd');

  /// Format numeric or string value to Indonesian Rupiah (e.g. Rp 150.000)
  static String formatRupiah(dynamic amount) {
    if (amount == null) return 'Rp 0';
    final num value;
    if (amount is num) {
      value = amount;
    } else if (amount is String) {
      value = num.tryParse(amount) ?? 0;
    } else {
      value = 0;
    }
    return _currencyFormat.format(value);
  }

  /// Format number with thousand separator (e.g. 15.000)
  static String formatNumber(dynamic value) {
    if (value == null) return '0';
    final num number;
    if (value is num) {
      number = value;
    } else if (value is String) {
      number = num.tryParse(value) ?? 0;
    } else {
      number = 0;
    }
    return _numberFormat.format(number);
  }

  /// Format date to dd MMM yyyy (e.g. 28 Sep 2026)
  static String formatDate(dynamic date) {
    if (date == null) return '-';
    DateTime? dt;
    if (date is DateTime) {
      dt = date;
    } else if (date is String) {
      dt = DateTime.tryParse(date);
    }
    if (dt == null) return '-';
    return _dateFormat.format(dt.toLocal());
  }

  /// Format datetime to dd MMM yyyy, HH:mm
  static String formatDateTime(dynamic date) {
    if (date == null) return '-';
    DateTime? dt;
    if (date is DateTime) {
      dt = date;
    } else if (date is String) {
      dt = DateTime.tryParse(date);
    }
    if (dt == null) return '-';
    return _dateTimeFormat.format(dt.toLocal());
  }

  /// Format time only (e.g. 14:30)
  static String formatTime(dynamic date) {
    if (date == null) return '-';
    DateTime? dt;
    if (date is DateTime) {
      dt = date;
    } else if (date is String) {
      dt = DateTime.tryParse(date);
    }
    if (dt == null) return '-';
    return _timeFormat.format(dt.toLocal());
  }

  /// Format date for API / database query (yyyy-MM-dd)
  static String toApiDate(DateTime date) {
    return _apiDateFormat.format(date);
  }
}
