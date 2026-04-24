# Network Configuration for QR Code

## Current Network Settings

- **Server IP Address**: `192.168.178.41`
- **Network**: `fritz.box` (Fritz!Box router)
- **Subnet Mask**: `255.255.255.0`
- **Default Gateway**: `192.168.178.1`

## QR Code URL

The QR code on the homepage generates a URL pointing to:
```
http://192.168.178.41/sheener/mobile_report.php
```

This allows mobile devices on the same network (fritz.box) to access the mobile reporting app directly.

## How It Works

1. **QR Code Generation**: The QR code is dynamically generated in `index.php` using the local IP address
2. **Mobile Access**: When users scan the QR code, their phone opens the mobile reporting app
3. **Network Requirement**: Devices must be on the same WiFi network (fritz.box) to access the app
4. **Offline Support**: Once loaded, the app works offline and syncs when connection is restored

## Updating the IP Address

If your server IP address changes, update it in `index.php`:

1. Open `index.php`
2. Find the `generateQRCode()` function (around line 1326)
3. Update the `localIP` variable:
   ```javascript
   const localIP = '192.168.178.41'; // Change this to your new IP
   ```

## Testing

1. Ensure your server is running on `192.168.178.41`
2. Open `index.php` in a browser
3. Verify the QR code appears
4. Scan the QR code with a phone on the same network
5. The mobile app should open at `http://192.168.178.41/sheener/mobile_report.php`

## Cloudflare Tunnel (Optional)

If you want external access (outside the local network), you can use Cloudflare Tunnel:

```bash
cloudflared tunnel --url http://192.168.178.41/sheener/index.php
```

This creates a public URL that can be accessed from anywhere. However, for local network access, the direct IP address (192.168.178.41) is recommended.

## Troubleshooting

### QR Code not working
- Verify the IP address is correct in `index.php`
- Check that XAMPP is running and accessible
- Ensure firewall allows connections on port 80
- Test by manually entering the URL in a browser

### Mobile device can't access
- Ensure mobile device is on the same WiFi network (fritz.box)
- Check that the server IP hasn't changed
- Verify XAMPP is running
- Try accessing `http://192.168.178.41/sheener/mobile_report.php` directly in mobile browser

### Network changed
- Update the IP address in `index.php` as described above
- Clear browser cache if QR code doesn't update
- Regenerate QR code by refreshing the page
