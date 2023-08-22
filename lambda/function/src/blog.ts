export type Blog = {
  id: string;
  title: string;
  content: string;
  created_at: string;
  updated_at: string;
  status: "PUBLISH" | string;
}