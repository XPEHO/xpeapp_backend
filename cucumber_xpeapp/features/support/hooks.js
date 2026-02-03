import { Before } from '@cucumber/cucumber';

Before(async function () {
  // Récupère un token JWT valide avant chaque scénario
  const res = await fetch('http://localhost:7830/wp-json/jwt-auth/v1/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username: 'wordpress_dev', password: 'wordpress_dev' })
  });
  const body = await res.json();
  this.token = body.token;
});