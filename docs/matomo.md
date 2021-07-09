# Matomo Integration

If you want to track visitors the open source software Matomo, then you need to

### Provide environement variable
in `.env.local` 
```
MATOMO_URL=https://my_matomo_server.org/
MATOMO_SITE_ID=12
MATOMO_USER_TOKEN=anonymous
```
You can also use a dedicate user token if you prefer

### Configure Cross Origin

In your Matomo instance, go to Administration > System > General settings and fill the Cross Origin Section. More info at https://matomo.org/faq/how-to/faq_18694/

### Allow visibility for anonymous user

If you use the anonymous token, then go to Administration > System > Users, and grant anonymous user the "view" permission for the website
