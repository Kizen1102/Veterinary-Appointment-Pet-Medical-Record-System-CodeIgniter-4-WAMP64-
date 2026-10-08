// Show/hide password: adds an eye button inside every password box on the page.
document.querySelectorAll('input[type="password"]').forEach(function (input) {
    // 1. Put the input inside a wrapper so the button can sit on top of it
    const wrapper = document.createElement('div');
    wrapper.className = 'password-wrapper';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    // 2. Create the eye button
    const button = document.createElement('button');
    button.type = 'button'; // "button", so clicking it does NOT submit the form
    button.className = 'password-toggle';
    button.setAttribute('aria-label', 'Show password');
    button.innerHTML = '<i class="bi bi-eye"></i>';
    wrapper.appendChild(button);

    // 3. On click: switch between hidden (password) and visible (text)
    button.addEventListener('click', function () {
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        button.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
});
