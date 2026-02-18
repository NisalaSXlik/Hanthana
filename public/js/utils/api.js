export async function api(controller, action, data = null)
{
    const url = `index.php?controller=${controller}&action=${action}&type=api)`

    const options = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    }

    if (data)
    {
        options.body = JSON.stringify(data)
    }
    
    const response = await fetch(url, options)
    return await response.json()
}