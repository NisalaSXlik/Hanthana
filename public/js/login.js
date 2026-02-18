import { api } from "./utils/api.js";

const loginForm = document.getElementById('login-form');
const successMessage = document.getElementById('success-message')
const errorMessage = document.getElementById('error-message')

loginForm.addEventListener('submit', async (e) =>
{
    e.preventDefault();
    const formData = new FormData(loginForm);
    const data = Object.fromEntries(formData.entries())
    console.log(data);

    try
    {
        const response = await api('Auth', 'login', data)
        if (response.status === 'success')
        {
            errorMessage.style.display = 'none'
            successMessage.style.display = 'block'
            successMessage.innerHTML = `Login Successful`

            setTimeout(() => {
                window.location.href = response.redirect;
            }, 1500)
        }
        else if (response.status === 'error')
        {
            successMessage.style.display = 'none'
            errorMessage.style.display = 'block';
            errorMessage.innerHTML = `${response.errors.join('<br>')}`
        }
    }
    catch (error)
    {
        console.error('Login error:', error);
        successMessage.style.display = 'none'
        errorMessage.style.display = 'block';
        errorMessage.innerHTML = 'A system error occurred. Please try again.';
    }
});