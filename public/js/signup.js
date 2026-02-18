import { api } from "./utils/api.js";

const signupForm = document.getElementById('signup-form');
const successMessage = document.getElementById('success-message')
const errorMessage = document.getElementById('error-message')

signupForm.addEventListener('submit', async (e) =>
{
    e.preventDefault();
    const password = signupForm.querySelector('input[name="password"]')
    const passwordConfirmation = signupForm.querySelector('input[name="password_confirmation"]')
    const phoneNumber = signupForm.querySelector('input[name="phone_number"]')

    if (!phoneNumber.value.match(/^0\d{9}$/))
    {
        phoneNumber.setCustomValidity('Please enter a valid 10-digit phone number.');
        phoneNumber.reportValidity();
        return;
    }

    if (password.value !== passwordConfirmation.value)
    {
        passwordConfirmation.setCustomValidity('Passwords do not match.');
        passwordConfirmation.reportValidity();
        return;
    }

    const formData = new FormData(signupForm);
    const data = Object.fromEntries(formData.entries())
    try
    {
        const response = await api('Auth', 'register', data)
        console.log(response)

        if (response.status === 'success')
        {
            errorMessage.style.display = 'none'
            successMessage.style.display = 'block'
            successMessage.innerHTML = 'Registration Successful';

            setTimeout(() => {
                window.location.href = response.redirect
            }, 1500)
        }
        else if (response.status === 'error')
        {
            successMessage.style.display = 'none'
            errorMessage.style.display = 'block'
            errorMessage.innerHTML = response.errors.join('<br>');
        }
    }
    catch (error)
    {
        console.error('Registration error:', error);
        successMessage.style.display = 'none'
        errorMessage.style.display = 'block';
        errorMessage.innerHTML = 'A system error occurred. Please try again.';
    }
});