// Reusable SweetAlert helper

function swalConfirm(options) {
    return Swal.fire({
        title: options.title || 'Are you sure?',
        text: options.text || '',
        icon: options.icon || 'warning',
        showCancelButton: true,
        confirmButtonColor: options.confirmColor || '#3085d6',
        cancelButtonColor: options.cancelColor || '#d33',
        confirmButtonText: options.confirmText || 'Yes',
        cancelButtonText: options.cancelText || 'Cancel'
    });
}

function swalSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success',
        text: message,
        timer: 1500,
        showConfirmButton: false
    });
}

function swalError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message
    });
}
