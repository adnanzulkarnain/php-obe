import { useState, useEffect } from 'react';

interface User {
  id_user: number;
  username: string;
  email: string;
  user_type: 'admin' | 'kaprodi' | 'dosen' | 'mahasiswa';
  ref_id: string;
  nama?: string;
}

export const useAuth = () => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Get user from localStorage
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      try {
        setUser(JSON.parse(storedUser));
      } catch (error) {
        console.error('Error parsing user data:', error);
      }
    }
    setIsLoading(false);
  }, []);

  const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    window.location.href = '/login';
  };

  return {
    user,
    isLoading,
    isAuthenticated: !!user,
    logout,
  };
};
