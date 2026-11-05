(function () {
    var root = document.documentElement;

    function currentTheme() {
        return root.getAttribute('data-theme') === 'night' ? 'night' : 'paper';
    }

    function paintThemeIcons() {
        var night = currentTheme() === 'night';
        document.querySelectorAll('[data-theme-toggle] i').forEach(function (icon) {
            icon.className = night ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        });
    }

    function setTheme(theme) {
        root.setAttribute('data-theme', theme);
        try {
            localStorage.setItem('klang-theme', theme);
        } catch (error) {
            /* ไม่บันทึกธีมได้ ก็ยังสลับในหน้านี้ได้ */
        }
        paintThemeIcons();
    }

    paintThemeIcons();
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            setTheme(currentTheme() === 'night' ? 'paper' : 'night');
        });
    });

    var sizeKey = 'klang-font';
    var size = 19;
    try {
        var storedSize = Number(localStorage.getItem(sizeKey));
        if (storedSize >= 16 && storedSize <= 28) {
            size = storedSize;
        }
    } catch (error) {
        size = 19;
    }

    function paintSize() {
        root.style.setProperty('--reader-size', size + 'px');
        var label = document.getElementById('fontSizeLabel');
        if (label) {
            label.textContent = String(size);
        }
    }

    paintSize();
    document.querySelectorAll('[data-font]').forEach(function (button) {
        button.addEventListener('click', function () {
            var step = button.getAttribute('data-font');
            if (step === 'reset') {
                size = 19;
            } else {
                size = Math.min(28, Math.max(16, size + Number(step)));
            }
            try {
                localStorage.setItem(sizeKey, String(size));
            } catch (error) {
                /* ใช้ขนาดในหน้านี้ต่อไป */
            }
            paintSize();
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === '1' || typeof Swal === 'undefined') {
                return;
            }
            event.preventDefault();
            Swal.fire({
                title: form.getAttribute('data-confirm') || 'ยืนยันหรือไม่',
                icon: form.getAttribute('data-confirm-icon') || 'warning',
                showCancelButton: true,
                confirmButtonText: form.getAttribute('data-confirm-ok') || 'ยืนยัน',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#9c3b2e',
                cancelButtonColor: '#6b625b',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                form.dataset.confirmed = '1';
                form.submit();
            });
        });
    });

    var flashEl = document.getElementById('flash-data');
    if (flashEl && typeof Swal !== 'undefined') {
        try {
            var flash = JSON.parse(flashEl.textContent || '{}');
            if (flash && flash.html) {
                var fallback = document.getElementById('flash-fallback');
                if (fallback) {
                    fallback.remove();
                }
                Swal.fire({
                    icon: flash.icon || 'info',
                    title: flash.title || 'แจ้งให้ทราบ',
                    html: flash.html,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#9c3b2e',
                    timer: flash.icon === 'success' ? 2400 : undefined,
                    timerProgressBar: flash.icon === 'success'
                });
            }
        } catch (error) {
            /* คงข้อความสำรองบนหน้าไว้ */
        }
    }

    document.querySelectorAll('[data-fill-username]').forEach(function (button) {
        button.addEventListener('click', function () {
            var user = document.getElementById('username');
            var pass = document.getElementById('password');
            if (user) {
                user.value = button.getAttribute('data-fill-username') || '';
            }
            if (pass) {
                pass.value = button.getAttribute('data-fill-password') || '';
            }
        });
    });

    var reader = document.getElementById('reader');
    if (reader) {
        document.addEventListener('keydown', function (event) {
            if (event.altKey || event.ctrlKey || event.metaKey) {
                return;
            }
            var tag = event.target && event.target.tagName ? event.target.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'textarea' || tag === 'select') {
                return;
            }
            if (event.key === 'ArrowLeft' && reader.dataset.prev) {
                window.location.href = reader.dataset.prev;
            }
            if (event.key === 'ArrowRight' && reader.dataset.next) {
                window.location.href = reader.dataset.next;
            }
        });
    }
}());
