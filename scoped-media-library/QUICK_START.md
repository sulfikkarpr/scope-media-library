# ⚡ Quick Start Guide

## Scoped Media Library - Get Started in 5 Minutes

---

## 1️⃣ Install (1 minute)

### Option A: Via WordPress Admin
1. Upload `scoped-media-library` folder to `/wp-content/plugins/`
2. Go to **Plugins** in WordPress admin
3. Click **Activate** on "Scoped Media Library"

### Option B: Via ZIP Upload
1. Go to **Plugins → Add New → Upload Plugin**
2. Choose the ZIP file
3. Click **Install Now** then **Activate**

---

## 2️⃣ Configure (2 minutes)

1. Go to **Settings → Scoped Media Library**

2. **Enable filtering:**
   - ✅ Check "Enable Filtering"

3. **Set dimension rules** (example for banners):
   ```
   Minimum Width: 1920
   Maximum Width: 3840
   Minimum Height: 600
   Maximum Height: 1200
   ```

4. **Click "Save Settings"**

---

## 3️⃣ Sync Images (1 minute)

1. On the same settings page, scroll down
2. Click **"Sync All Image Dimensions"**
3. Wait for success message
4. Done! ✅

---

## 4️⃣ Test (1 minute)

1. Create/edit any post or page
2. Click **"Add Media"** button
3. **Only images matching your rules will appear!** 🎉

---

## 🎯 Common Configurations

### Banner Images
```
Min Width: 1920px | Max Width: 3840px
Min Height: 600px | Max Height: 1200px
```

### Square Icons
```
Min Width: 100px | Max Width: 200px
Min Height: 100px | Max Height: 200px
```

### HD Images Only
```
Min Width: 1920px | Max Width: (empty)
Min Height: 1080px | Max Height: (empty)
```

### Portrait Images
```
Min Width: 600px  | Max Width: 1200px
Min Height: 800px | Max Height: 1600px
```

---

## ⚙️ Optional: Enable Fallback Mode

**Want admins to see ALL images?**

1. In settings, check **"Enable Fallback Mode"**
2. Select **Administrator** role
3. Save settings
4. Admins will now see all images (editors will see filtered)

---

## 🆘 Troubleshooting

### Images not filtering?
- ✅ Make sure "Enable Filtering" is checked
- ✅ Run "Sync All Image Dimensions"
- ✅ Save your settings

### Too few images showing?
- ✅ Check your min/max values aren't too restrictive
- ✅ Verify actual image dimensions in Media Library

### Too many images showing?
- ✅ Make sure fallback mode isn't enabled for your user role
- ✅ Double-check your dimension ranges

---

## 🎓 Next Steps

- Read the [full README.md](README.md) for detailed documentation
- Check [INSTALLATION.md](INSTALLATION.md) for advanced setup
- Review [CHANGELOG.md](CHANGELOG.md) for version history

---

## 📞 Need Help?

- 📖 Check the documentation files
- 🐛 Report issues on GitHub
- 💬 Ask on WordPress.org support forum

---

## ✅ Quick Checklist

- [ ] Plugin installed and activated
- [ ] Settings page accessible  
- [ ] Filtering enabled
- [ ] Dimension rules set
- [ ] Settings saved
- [ ] Images synced
- [ ] Tested media modal
- [ ] Verified filtered results

**Done? You're all set!** 🚀

---

**Time invested:** ~5 minutes  
**Time saved per image search:** 30-60 seconds  
**ROI:** Immediate! ⚡