import React from 'react'
interface Props {  login: string
}
export default function login({ login }: { login: string }) {
  return (
    <div>
      This is the login page for {login}
    </div>
  )
}
