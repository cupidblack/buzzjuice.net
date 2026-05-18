import React, { useState, useEffect } from "react";
import {
  DialogActions,
  Dialog,
  DialogContent,
  DialogTitle,
  AppBar,
  Toolbar,
  Typography,
  TextField,
  Button,
  InputAdornment,
  IconButton,
  Box,
  FormControl,
  Grid,
  Card,
  CardContent,
  CardActions,
  Snackbar,
  Skeleton,
} from "@mui/material";
import Link from '@mui/material/Link';
import { createTheme, ThemeProvider } from "@mui/material/styles";
import CssBaseline from "@mui/material/CssBaseline";
import SearchIcon from "@mui/icons-material/Search";
import { __ } from "@wordpress/i18n";
import Switch from "@mui/material/Switch";
import FormControlLabel from "@mui/material/FormControlLabel";
import CloseIcon from "@mui/icons-material/Close";
import { ReactComponent as MyCredLogo } from "./icons/mycred-logo.svg";
import { ReactComponent as MyCredDialogLogo } from "./icons/mycred-dialog-logo.svg";
import { ReactComponent as UpgradeVector } from "./icons/upgrade-vector.svg";
import { ReactComponent as BackgroundSVG } from "./icons/popup-background.svg";
import { styled } from "@mui/material/styles";
import CheckCircleIcon from "@mui/icons-material/CheckCircle";
import "@fontsource/figtree";
import "@fontsource/figtree/700.css";

import addOnsData from "./addons.json";

const theme = createTheme({
  palette: {
    primary: { main: "#4A90E2" },
    secondary: { main: "#E64A19" },
  },
  typography: { fontFamily: "Roboto, sans-serif" },
});

const App = () => {
  const [snackbarOpen, setSnackbarOpen] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [Addons, setAddons] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [open, setOpen] = useState(false);
  const [addonsData, setAddonsData] = useState(addOnsData);

  const handleOpen = () => {
    setOpen(true);
  };

  const ToggleSwitch = styled(Switch)(({ theme }) => ({
    width: 42,
    height: 20,
    padding: 0,
    display: "flex",
    "&:active": {
      "& .MuiSwitch-thumb": {
        width: 15,
      },
      "& .MuiSwitch-switchBase.Mui-checked": {
        transform: "translateX(22px)",
      },
    },
    "& .MuiSwitch-switchBase": {
      padding: 2,
      "&.Mui-checked": {
        transform: "translateX(22px)",
        color: "#fff",
        "& + .MuiSwitch-track": {
          opacity: 1,
          backgroundColor: "#5F2CED",
        },
      },
    },
    "& .MuiSwitch-thumb": {
      boxShadow: "0 2px 4px 0 rgb(0 35 11 / 20%)",
      width: 16,
      height: 16,
      borderRadius: 16 / 2,
      transition: theme.transitions.create(["width"], {
        duration: 200,
      }),
    },
    "& .MuiSwitch-track": {
      borderRadius: 20 / 2,
      opacity: 1,
      backgroundColor: "#E0E0E0",
      boxSizing: "border-box",
    },
  }));

  const handleClose = () => {
    setOpen(false);
  };

  function contains(data, value) {
    if (Array.isArray(data)) {
        return data.includes(value);
    } else if (data && typeof data === "object") {
        return Object.values(data).includes(value);
    }
    return false;
}

  const fetchAddOns = async () => {
    try {
      setLoading(true);
      const siteUrl = `${window.mycredAddonsData.root}mycred/v1/get-core-addons`;

      const response = await fetch(siteUrl, {
        method: "GET",
        headers: {
          'X-WP-Nonce': window.mycredAddonsData.nonce,
          "Content-Type": "application/json",
        },
      });

      if (!response.ok) {
        throw new Error("Network response was not ok");
      }

      const Addons = await response.json();

      setAddons(Addons);
    } catch (error) {
      setSnackbarMessage("Error fetching add-ons: " + error.message);
      setSnackbarOpen(true);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAddOns();
  }, []);

  const handleToggleClick = async (addOn) => {
  if (loading) return;

  setLoading(true);
  try {
    const siteUrl = `${window.mycredAddonsData.root}mycred/v1/enable-core-addon`;

    const response = await fetch(siteUrl, {
      method: "POST",
      headers: {
        'X-WP-Nonce': window.mycredAddonsData.nonce,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        addOnSlug: addOn.slug,
        addOnTitle: addOn.title,
      }),
    });

   const result = await response.json();
      fetchAddOns();
      setSnackbarMessage(result.message);
      setSnackbarOpen(true);
  } catch (error) {

    setSnackbarOpen(true);
  } finally {
    setLoading(false);
  }
  };


  const handleSearchData = (event) => {
      setSearchTerm(event.target.value);
  };

  const renderSVG = (iconSlug) => {
    try {
      const IconComponent = require(`./icons/${iconSlug}.svg`).default;

      if (IconComponent.startsWith("data:image/svg+xml")) {
        return (
          <div
            dangerouslySetInnerHTML={{
              __html: atob(IconComponent.split(",")[1]),
            }}
          />
        );
      }

      // If it's recognized as a React component
      return <IconComponent width={24} height={24} />;
    } catch (error) {
      console.error(`SVG not found for icon name: ${iconSlug}`);
      return null;
    }
  };

  const filteredAddons = addonsData
    .filter((addOn) =>
      addOn.title.toLowerCase().includes(searchTerm.toLowerCase())
    );

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />

      <Box sx={{ flexGrow: 1 }}>
        <AppBar
          color="default"
          elevation={0}
          sx={{
            boxShadow: "0px 4px 8.4px 0px rgba(94, 44, 237, 0.06)",
            border: "none",
            position: "static",
            backgroundColor: "#FFFFFF",
          }}
        >
          <Toolbar>
            <Typography
              variant="h4"
              sx={{
                flexGrow: 1,
                display: "flex",
                alignItems: "center",
                gap: "8px",
              }}
            >
              <MyCredLogo />
            </Typography>

            <TextField
              variant="outlined"
              placeholder="Search"
              value={searchTerm}
              onChange={handleSearchData}
              sx={{
                padding: "14px",
              }}
              InputProps={{
                startAdornment: (
                  <InputAdornment
                    position="start"
                    sx={{ color: "#036666" }}
                  ></InputAdornment>
                ),
                sx: {
                  "& .MuiOutlinedInput-notchedOutline": {
                    border: "none",
                  },
                },
              }}
            />
          </Toolbar>
        </AppBar>
      </Box>

      <Box
        sx={{
          padding: 4,
          backgroundColor: "#F0F4FF",
        }}
      >
        <Typography
          variant="h5"
          sx={{
            fontWeight: "500",
            flexGrow: 1,
            display: "flex",
            alignItems: "center",
            gap: "8px",
          }}
        >
          {__("Built-in Addons", "mycred")}
        </Typography>
        <br />

        <Grid container spacing={3}>
          {filteredAddons.map((addOn) => (
            <Grid item xs={12} sm={6} md={4} key={addOn.slug}>
              <Card
                sx={{
                  width: "100%",
                  height: "auto",
                  position: "relative",
                  borderRadius: "8px",
                  border: "1px solid transparent",
                  display: "flex",
                  flexDirection: "column",
                }}
              >
                {/* Card Content */}
                <CardContent>
                  {loading ? (
                    <>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        alignItems="flex-start"
                        mb={2}
                      >
                        <Skeleton variant="circular" width={40} height={40} />
                        <Skeleton variant="circular" width={24} height={24} />
                      </Box>
                      <Skeleton variant="text" width="50%" height={32} />
                      <Skeleton variant="text" width="80%" />
                      <Skeleton variant="text" width="60%" />
                    </>
                  ) : (
                    <>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        alignItems="flex-start"
                        mb={2}
                      >
                        <Box>{renderSVG(addOn.slug)}</Box>
                      </Box>

                      <Typography sx={{ color: "#2D1572" }} variant="h6" mb={1}>
                        {addOn.title}
                      </Typography>

                      <Typography variant="body2" mb={2}>
                        {addOn.description.slice(0, 110) +
                          (addOn.description.length > 110 ? "..." : "")}
                      </Typography>
                    </>
                  )}
                </CardContent>

                <Box
                  sx={{
                    backgroundColor: "#F6F9FF",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "space-between",
                    padding: "16px",
                  }}
                >
                  
                  <Link
                    component="a"
                    href={addOn.link}
                    target="_blank"
                    rel="noopener noreferrer"
                    variant="body2"
                    color="primary"
                    sx={{
                      color: "#9496C1",
                      textDecoration: "none",
                      cursor: "pointer",
                    }}
                  >
                    Learn More
                  </Link>

                   <FormControlLabel
              control={
                <ToggleSwitch
                  checked={contains(Addons, addOn.slug)}
                  onChange={() => handleToggleClick(addOn)}
                  disabled={loading}
                  sx={{
                    marginRight: "16px",
                  }}
                />
              }
              label={
                loading
                  ? "Loading..."
                  : contains(Addons, addOn.slug)
                  ? "Enable"
                  : "Disable"
              }
              labelPlacement="start"
              sx={{
                marginLeft: "10px",
                gap: "10px",
                color: contains(Addons, addOn.slug) ? "#5F2CED" : "#9496C1",
              }}
            />
                </Box>
              </Card>
            </Grid>
          ))}
        </Grid>
      </Box>

      <Snackbar
        open={snackbarOpen}
        onClose={() => setSnackbarOpen(false)}
        autoHideDuration={6000}
        anchorOrigin={{ vertical: "bottom", horizontal: "right" }}
        sx={{
          "& .MuiSnackbarContent-root": {
            backgroundColor: "green",
            color: "#fff",
            display: "flex",
            alignItems: "center",
            fontSize: "16px",
          },
        }}
        message={
          <Box display="flex" alignItems="center">
            <CheckCircleIcon sx={{ mr: 1, color: "#fff" }} />
            <Typography>{snackbarMessage}</Typography>
          </Box>
        }
      />
    </ThemeProvider>
  );
};

export default App;
