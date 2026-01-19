import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // تحسينات الأداء
  reactStrictMode: false, // تعطيل في development للسرعة
  
  // استخدام Webpack بدلاً من Turbopack (أكثر استقراراً)
  webpack: (config, { dev, isServer }) => {
    // تحسينات للـ development
    if (dev) {
      config.cache = true;
      // تقليل الـ compilation time
      config.optimization = {
        ...config.optimization,
        minimize: false,
      };
    }
    
    // تحسينات للـ production
    if (!dev) {
      config.optimization = {
        ...config.optimization,
        splitChunks: {
          chunks: 'all',
          cacheGroups: {
            vendor: {
              test: /[\\/]node_modules[\\/]/,
              name: 'vendors',
              chunks: 'all',
            },
          },
        },
      };
    }
    
    return config;
  },
  
  // إعدادات الـ API
  async rewrites() {
    return [
      { source: "/dashboard/users", destination: "/users" },
      { source: "/dashboard/users/create", destination: "/users/create" },
      { source: "/dashboard/users/:id", destination: "/users/:id" },
      { source: "/dashboard/users/:id/edit", destination: "/users/:id/edit" },
    ];
  },
  
  // إعدادات الـ headers
  async headers() {
    return [
      {
        source: '/api/:path*',
        headers: [
          { key: 'Cache-Control', value: 'public, max-age=300, s-maxage=300' },
        ],
      },
    ];
  },
};

export default nextConfig;
