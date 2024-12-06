# 通知提示音

为了添加通知提示音，你需要：

1. 准备一个 MP3 格式的提示音文件
2. 将文件命名为 `notification.mp3`
3. 放置在 `web/public` 目录下

你可以：
1. 使用任何音频编辑软件创建提示音
2. 从网上下载免费的提示音
3. 使用 ffmpeg 生成：
```bash
ffmpeg -f lavfi -i "sine=frequency=1000:duration=0.3" -ar 44100 notification.mp3
```
