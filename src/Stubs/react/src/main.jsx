import React from 'react'
import ReactDOM from 'react-dom/client'
import AppRouter from './router/AppRouter'

ReactDOM.createRoot(document.getElementById('app')).render(
    <React.StrictMode>
        <AppRouter />
    </React.StrictMode>
)
