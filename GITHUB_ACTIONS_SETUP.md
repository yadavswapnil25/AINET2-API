# GitHub Actions Deployment Setup

## Current Status
The GitHub Actions workflow is configured but **secrets are not set up**, which is why you're seeing the error:
```
error: missing server host
```

## Options

### Option 1: Set Up GitHub Actions (Recommended for Auto-Deployment)

1. **Go to your GitHub repository**
2. **Settings** > **Secrets and variables** > **Actions**
3. **Click "New repository secret"** and add:

   - **Name:** `VPS_HOST`
     **Value:** Your server IP or domain (e.g., `119.18.55.125`)

   - **Name:** `VPS_USER`
     **Value:** Your SSH username (e.g., `root`)

   - **Name:** `VPS_SSH_KEY`
     **Value:** Your private SSH key content (the entire key, including `-----BEGIN OPENSSH PRIVATE KEY-----`)

   - **Name:** `VPS_PORT` (Optional)
     **Value:** SSH port (default: `22`)

4. **Test the workflow:**
   - Go to **Actions** tab
   - Click **"Deploy to VPS"** workflow
   - Click **"Run workflow"** button

### Option 2: Disable GitHub Actions (If Not Using It)

If you're deploying manually or using a different method, you can:

**Delete the workflow file:**
```bash
rm .github/workflows/deploy.yml
```

Or **disable it** by adding this at the top of the workflow:
```yaml
on:
  workflow_dispatch: # Only manual trigger
```

### Option 3: Use Manual Deployment (Current Method)

Continue using your current deployment method:
```bash
# On your VPS server
cd /var/www/AINET2-API
git pull
sudo bash deploy.sh
```

## Getting Your SSH Key

If you need to generate or find your SSH key:

```bash
# Check if you have an SSH key
ls -la ~/.ssh/

# If you don't have one, generate it:
ssh-keygen -t ed25519 -C "your_email@example.com"

# Copy public key to server (if not already done)
ssh-copy-id user@your-server-ip

# Display private key (to add to GitHub secrets)
cat ~/.ssh/id_ed25519
```

## Troubleshooting

- **"missing server host"**: Secrets are not configured → Follow Option 1
- **"Permission denied"**: SSH key is incorrect → Regenerate and add correct key
- **"Connection refused"**: Server IP/port is wrong → Check VPS_HOST and VPS_PORT
- **Workflow runs but fails**: Check server logs and ensure `deploy.sh` has execute permissions

## Recommendation

Since you're already deploying manually, you can either:
1. **Keep manual deployment** - Delete `.github/workflows/deploy.yml`
2. **Set up auto-deployment** - Configure secrets (Option 1) for automatic deployments on every push

