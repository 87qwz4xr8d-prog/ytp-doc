(function () {
    try {
        var theme = localStorage.getItem('klang-theme');
        if (theme === 'night' || theme === 'paper') {
            document.documentElement.setAttribute('data-theme', theme);
        }
    } catch (error) {
        /* ค่าที่เก็บในเบราว์เซอร์ใช้ไม่ได้ ก็เปิดด้วยธีมกระดาษ */
    }
}());
